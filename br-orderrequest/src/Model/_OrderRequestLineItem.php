<?php

/**
 * @copyright   Copyright (C) 2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2025-11-11
 */

namespace BR\Extension\OrderRequest\Model;

use Combodo\iTop\Service\Events\EventData;
use cmdbAbstractObject;
use CMDBSource;
use MetaModel;
use DBObjectSearch;
use DBObjectSet;
use DBObject;
use ItopCounter;
use AttributeDate;
use AttributeDateTime;

/**
 * The generated class OrderRequestLineItem will extend this class
 * (configured via <php_parent> in the XML).
 */
class _OrderRequestLineItem extends cmdbAbstractObject
{


    /**
     * Ermittelt die OrderRequest-ID aus Objekt und UI-Kontext.
     */
    private function resolveOrderIdFromContext(array &$aContextParam): int
    {
        // 1) aus dem Objekt selbst
        $iOrderId = (int)($this->Get('order_request_id') ?: 0);
        if ($iOrderId > 0) {
            return $iOrderId;
        }

        // 2) aus einem direkten Kontextparameter
        if (isset($aContextParam['order_request_id'])) {
            $iOrderId = (int)$aContextParam['order_request_id'];
            if ($iOrderId > 0) {
                return $iOrderId;
            }
        }

        // 3) aus Linked-Set-Kontext (source_obj = OrderRequest)
        if (isset($aContextParam['source_obj']) && is_object($aContextParam['source_obj'])) {
            // keine harte Abhängigkeit: nur prüfen, ob es wie ein DBObject ist
            if ($aContextParam['source_obj'] instanceof \DBObject) {
                $sFinal = (string)$aContextParam['source_obj']->Get('finalclass');
                if ($sFinal === 'OrderRequest') {
                    $iOrderId = (int)$aContextParam['source_obj']->GetKey();
                    if ($iOrderId > 0) {
                        return $iOrderId;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Liefert die nächste Positionsnummer (1 oder MAX(line_number)+1) für eine BANF.
     * Nutzt SQL für MAX() und fällt bei Bedarf auf OQL zurück.
     */
    private function getNextLineNumber(int $iOrderId): int
    {
        if ($iOrderId <= 0) {
            return 1;
        }

        // Schnellweg: SQL MAX()
        $row = CMDBSource::QuerySingleRow(
            'SELECT MAX(line_number) AS max_ln FROM orderrequestlineitem WHERE order_request_id = :id',
            ['id' => $iOrderId]
        );
        $iMax = (int)($row['max_ln'] ?? 0);
        if ($iMax > 0) {
            return $iMax + 1;
        }

        // Fallback (sollte selten nötig sein): OQL-Iteration
        $oSearch = DBObjectSearch::FromOQL('SELECT OrderRequestLineItem WHERE order_request_id = :id');
        $oSet    = new DBObjectSet($oSearch, [], ['id' => $iOrderId]);

        $iMax = 0;
        while ($oItem = $oSet->Fetch()) {
            $i = (int)($oItem->Get('line_number') ?: 0);
            if ($i > $iMax) {
                $iMax = $i;
            }
        }
        return ($iMax > 0) ? $iMax + 1 : 1;
    }


    /**
     * PrefillCreationForm: setzt line_number = 1 bzw. max+1 für dieselbe BANF.
     * Greift in der UI (auch beim "Add" aus dem LinkedSet).
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        // nur vorbefüllen, wenn leer
        $iLine = (int)($this->Get('line_number') ?: 0);
        if ($iLine > 0) {
            return;
        }

        $iOrderId = $this->resolveOrderIdFromContext($aContextParam);
        $this->Set('line_number', $this->getNextLineNumber($iOrderId));
    }

    /* ========= Event callbacks ========= */

    /**
     * EVENT_DB_COMPUTE_VALUES: total = quantity * unit_price_estimated (gerundet)
     * Name beibehalten (deiner Version), plus Wrapper darunter für ältere XMLs.
     */
    public function OnLineItemComputeValues(EventData $oEventData): void
    {
        $qty  = (float)($this->Get('quantity') ?: 0);
        $unit = (float)($this->Get('unit_price_estimated') ?: 0);
        $this->Set('total_price_estimated', round($qty * $unit, 2));
    }

    /**
     * total_price_estimated read-only (initial flags)
     */
    public function OnLineItemSetInitialAttributesFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('total_price_estimated', OPT_ATT_READONLY);
    }

    /**
     * total_price_estimated read-only (contextual flags)
     */
    public function OnLineItemSetAttributesFlags(EventData $oEventData): void
    {
        $this->ForceAttributeFlags('total_price_estimated', OPT_ATT_READONLY);
    }


    /**
     * Validierungen: Menge > 0, Preis >= 0, UoM Pflicht; Duplikat-Hinweis.
     */
    public function OnLineItemCheckToWrite(EventData $oEventData): void
    {
        // qty > 0
        $qty = $this->Get('quantity');
        if ($qty === null || $qty === '' || (float)$qty <= 0) {
            $this->AddCheckIssue(\Dict::S('Class:OrderRequestLineItem/Error:QtyMustBePositive'));
        }

        // unit price >= 0
        $price = $this->Get('unit_price_estimated');
        if ($price !== null && $price !== '' && (float)$price < 0) {
            $this->AddCheckIssue(\Dict::S('Class:OrderRequestLineItem/Error:UnitPriceNegative'));
        }

        // UoM required
        $uom = (string)$this->Get('uom');
        if ($uom === '') {
            $this->AddCheckIssue(\Dict::S('Class:OrderRequestLineItem/Error:UomRequired'));
        }

        // Duplicate warning: same BANF + same name + same UoM (other id)
        $iOrderId = (int)$this->Get('order_request_id');
        if ($iOrderId > 0 && $uom !== '') {
            $oSearch = DBObjectSearch::FromOQL(
                'SELECT OrderRequestLineItem WHERE order_request_id = :id AND name = :name AND uom = :uom AND id != :me'
            );
            $oSet = new DBObjectSet($oSearch, [], [
                'id'   => $iOrderId,
                'name' => (string)$this->Get('name'),
                'uom'  => $uom,
                'me'   => (int)$this->GetKey(),
            ]);
            if ($oSet->Count() > 0) {
                $this->AddCheckWarning(\Dict::S('Class:OrderRequestLineItem/Warning:DuplicateNameUom'));
            }
        }
    }



    /**
     * (Optional) Safety net für non-UI writes (CSV/REST):
     * setzt line_number auf 1 oder max+1, wenn leer.
     * Aktivieren über EVENT_DB_BEFORE_WRITE Listener im XML.
     */
    public function OnLineItemBeforeWrite(EventData $oEventData): void
    {
        $iLine = (int)($this->Get('line_number') ?: 0);
        if ($iLine > 0) {
            return;
        }

        $iOrderId = (int)($this->Get('order_request_id') ?: 0);
        $this->Set('line_number', $this->getNextLineNumber($iOrderId));
    }
}
