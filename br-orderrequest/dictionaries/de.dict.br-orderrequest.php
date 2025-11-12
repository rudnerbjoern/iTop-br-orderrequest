<?php

/**
 * German dictionary for br-orderrequest
 *
 * Covers:
 * - Menus & Dashboard labels
 * - Classes: OrderRequest, OrderRequestLineItem, OrderRequestType
 * - Attributes, statuses, stimuli
 * - Validation messages & warnings used in PHP
 *
 * Notes:
 * - Some optional keys are present for future use (eg. cost_center, expected_delivery_date).
 *   Remove them if you don't plan to add the attributes.
 *
 * @copyright   Copyright (C) 2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2025-11-12
 */

// ─────────────────────────────────────────────────────────────────────────────
// Menüs & Dashboard
// ─────────────────────────────────────────────────────────────────────────────
/** @disregard P1009 Undefined type Dict */
Dict::Add('DE DE', 'German', 'Deutsch', array(
    'Menu:OrderRequest' => 'Bestellanforderungen (BANF)',
    'Menu:OrderRequest+' => 'Interne Bedarfs-/Bestellanforderungen (BANF) erstellen, verfolgen und genehmigen.',
    'Menu:OrderRequestSpace' => 'Bestellanforderungen',
    'Menu:OrderRequestSpace+' => 'Übersicht und Schnellzugriff',
    'Menu:OrderRequestSpace:Header' => 'Übersicht',
));

// ─────────────────────────────────────────────────────────────────────────────
// Klasse: OrderRequestType
// ─────────────────────────────────────────────────────────────────────────────
/** @disregard P1009 Undefined type Dict */
Dict::Add('DE DE', 'German', 'Deutsch', array(
    'Class:OrderRequestType' => 'Bestellanforderungs-Typ',
    'Class:OrderRequestType/Plural' => 'Bestellanforderungs-Typen',
    'Class:OrderRequestType+' => 'Konfigurierbare Anfragetypen (z. B. Server, Netzwerk, Firewall) mit Standard-Genehmiger.',

    'Class:OrderRequestType/Attribute:org_id'   => 'Organisation',
    'Class:OrderRequestType/Attribute:org_id+'  => 'Zugehörige Organisation.',
    'Class:OrderRequestType/Attribute:org_name' => 'Organisationsname',
    'Class:OrderRequestType/Attribute:org_name+' => 'Lesbarer Name der Organisation.',

    'Class:OrderRequestType/Attribute:name'     => 'Name',
    'Class:OrderRequestType/Attribute:name+'    => 'Anzeigename des Typs.',
    'Class:OrderRequestType/Attribute:code'     => 'Code',
    'Class:OrderRequestType/Attribute:code+'    => 'Kurzer eindeutiger Code (pro Organisation).',

    'Class:OrderRequestType/Attribute:description'   => 'Beschreibung',
    'Class:OrderRequestType/Attribute:description+'  => 'Optionale funktionale/technische Beschreibung.',

    'Class:OrderRequestType/Attribute:default_approver_id'   => 'Standard-Genehmiger (technisch)',
    'Class:OrderRequestType/Attribute:default_approver_id+'  => 'Person, die automatisch als technischer Genehmiger vorgeschlagen wird.',
    'Class:OrderRequestType/Attribute:default_approver_name' => 'Standard-Genehmiger (Name)',
    'Class:OrderRequestType/Attribute:default_approver_name+' => 'Lesbarer Name des technischen Standard-Genehmigers.',

    'Class:OrderRequestType/Attribute:status'   => 'Status',
    'Class:OrderRequestType/Attribute:status+'  => 'Steuert, ob der Typ in neuen Anfragen auswählbar ist.',
    'Class:OrderRequestType/Attribute:status/Value:active'   => 'Aktiv',
    'Class:OrderRequestType/Attribute:status/Value:active+'  => 'Typ ist in Bestellanforderungen auswählbar.',
    'Class:OrderRequestType/Attribute:status/Value:inactive' => 'Inaktiv',
    'Class:OrderRequestType/Attribute:status/Value:inactive+' => 'Typ ist in neuen Bestellanforderungen nicht auswählbar.',

    'Class:OrderRequestType/Attribute:requires_budget_owner_approval'   => 'Genehmigung durch Budgetverantwortliche:n',
    'Class:OrderRequestType/Attribute:requires_budget_owner_approval+'  => 'Falls „Ja“, ist zusätzlich die Genehmigung durch die/den Budgetverantwortliche:n erforderlich.',
    'Class:OrderRequestType/Attribute:requires_budget_owner_approval/Value:yes' => 'ja',
    'Class:OrderRequestType/Attribute:requires_budget_owner_approval/Value:no'  => 'nein',

    // Optional (nur verwenden, wenn das Feld im XML vorhanden ist)
    'Class:OrderRequestType/Attribute:cost_center_default'   => 'Standard-Kostenstelle',
    'Class:OrderRequestType/Attribute:cost_center_default+'  => 'Optionale Standard-Kostenstelle zur Vorbelegung in Anfragen.',
));

// ─────────────────────────────────────────────────────────────────────────────
// Klasse: OrderRequest (Ticket-Ableitung)
// ─────────────────────────────────────────────────────────────────────────────
/** @disregard P1009 Undefined type Dict */
Dict::Add('DE DE', 'German', 'Deutsch', array(
    'OrderRequest:approval' => 'Genehmigung',
    'OrderRequest:costs'    => 'Kosten & Beschaffung',

    'Class:OrderRequest' => 'Bestellanforderung (BANF)',
    'Class:OrderRequest/Plural' => 'Bestellanforderungen (BANF)',
    'Class:OrderRequest+' => 'Interne Bedarfs-/Bestellanforderung (BANF) als spezialisierter Ticket-Typ.',

    'Class:OrderRequest/Attribute:status' => 'Status',
    'Class:OrderRequest/Attribute:status+' => 'Lebenszyklusstatus der Bestellanforderung.',
    'Class:OrderRequest/Attribute:status/Value:draft'            => 'Entwurf',
    'Class:OrderRequest/Attribute:status/Value:draft+'           => 'Angaben werden erfasst.',
    'Class:OrderRequest/Attribute:status/Value:submitted'        => 'Eingereicht',
    'Class:OrderRequest/Attribute:status/Value:submitted+'       => 'Zur Prüfung eingereicht.',
    'Class:OrderRequest/Attribute:status/Value:in_review'        => 'In Prüfung',
    'Class:OrderRequest/Attribute:status/Value:in_review+'       => 'Interne Sichtung/Prüfung läuft.',
    'Class:OrderRequest/Attribute:status/Value:waiting_approval' => 'Wartet auf Genehmigung',
    'Class:OrderRequest/Attribute:status/Value:waiting_approval+' => 'Genehmigung steht aus.',
    'Class:OrderRequest/Attribute:status/Value:approved'         => 'Genehmigt',
    'Class:OrderRequest/Attribute:status/Value:approved+'        => 'Technisch genehmigt.',
    'Class:OrderRequest/Attribute:status/Value:rejected'         => 'Abgelehnt',
    'Class:OrderRequest/Attribute:status/Value:rejected+'        => 'Von der/dem Genehmiger:in abgelehnt.',
    'Class:OrderRequest/Attribute:status/Value:procurement'      => 'Beschaffung',
    'Class:OrderRequest/Attribute:status/Value:procurement+'     => 'An den Einkauf übergeben.',
    'Class:OrderRequest/Attribute:status/Value:closed'           => 'Abgeschlossen',
    'Class:OrderRequest/Attribute:status/Value:closed+'          => 'Vorgang abgeschlossen.',

    'Class:OrderRequest/Attribute:request_type_id'   => 'Anfragetyp',
    'Class:OrderRequest/Attribute:request_type_id+'  => 'Kategorie der Anforderung (z. B. Server, Netzwerk, Firewall).',
    'Class:OrderRequest/Attribute:request_type_name' => 'Anfragetyp (Name)',
    'Class:OrderRequest/Attribute:request_type_name+' => 'Lesbarer Name des Anfragetypen.',

    'Class:OrderRequest/Attribute:description' => 'Fachliche Begründung',
    'Class:OrderRequest/Attribute:description+' => 'Warum wird dies benötigt? Compliance, Audit, Business Impact …',

    // Optional (nur verwenden, wenn im XML vorhanden)
    'Class:OrderRequest/Attribute:cost_center' => 'Kostenstelle',
    'Class:OrderRequest/Attribute:cost_center+' => 'Verrechnete Kostenstelle.',
    'Class:OrderRequest/Attribute:expected_delivery_date' => 'Erwartetes Lieferdatum',
    'Class:OrderRequest/Attribute:expected_delivery_date+' => 'Gewünschter Liefer-/Bereitstellungstermin.',

    'Class:OrderRequest/Attribute:estimated_total_cost'  => 'Geschätzte Gesamtkosten',
    'Class:OrderRequest/Attribute:estimated_total_cost+' => 'Summe der Positionsbeträge (automatisch berechnet).',

    'Class:OrderRequest/Attribute:technical_approver_id'   => 'Technischer Genehmiger',
    'Class:OrderRequest/Attribute:technical_approver_id+'  => 'Für die technische Genehmigung verantwortliche Person.',
    'Class:OrderRequest/Attribute:technical_approver_name' => 'Technischer Genehmiger (Name)',
    'Class:OrderRequest/Attribute:technical_approver_name+' => 'Lesbarer Name des technischen Genehmigers.',

    'Class:OrderRequest/Attribute:approved_by_id'   => 'Genehmigt/abgelehnt von',
    'Class:OrderRequest/Attribute:approved_by_id+'  => 'Person, die genehmigt oder abgelehnt hat.',
    'Class:OrderRequest/Attribute:approved_by_name' => 'Genehmigt von (Name)',
    'Class:OrderRequest/Attribute:approved_by_name+' => 'Lesbarer Name der/des Genehmiger:in/Ablehnenden.',

    'Class:OrderRequest/Attribute:approval_comment'       => 'Genehmigungs-Kommentar',
    'Class:OrderRequest/Attribute:approval_comment+'      => 'Begründung oder Auflagen der/des Genehmiger:in.',
    'Class:OrderRequest/Attribute:approval_request_date'  => 'Genehmigung angefordert am',
    'Class:OrderRequest/Attribute:approval_request_date+' => 'Zeitpunkt der Genehmigungsanfrage.',
    'Class:OrderRequest/Attribute:approval_date'          => 'Entscheidung am',
    'Class:OrderRequest/Attribute:approval_date+'         => 'Zeitpunkt der Genehmigung/Ablehnung.',

    'Class:OrderRequest/Attribute:procurement_reference'   => 'Beschaffungsreferenz',
    'Class:OrderRequest/Attribute:procurement_reference+'  => 'Referenz des Einkaufs (z. B. Bestellnummer/PO, Request-ID).',

    'Class:OrderRequest/Attribute:line_items'       => 'Positionen',
    'Class:OrderRequest/Attribute:line_items+'      => 'Angeforderte Artikel/Dienstleistungen als Positionen.',

    'Class:OrderRequest/Attribute:parent_request_id'  => 'Zugehörige Serviceanfrage',
    'Class:OrderRequest/Attribute:parent_request_id+' => 'Verknüpfte Serviceanfrage (falls vorhanden).',
    'Class:OrderRequest/Attribute:parent_request_ref' => 'Serviceanfrage-Ref.',
    'Class:OrderRequest/Attribute:parent_incident_id' => 'Zugehöriger Incident',
    'Class:OrderRequest/Attribute:parent_incident_ref' => 'Incident-Ref.',
    'Class:OrderRequest/Attribute:parent_problem_id'  => 'Zugehöriges Problem',
    'Class:OrderRequest/Attribute:parent_problem_ref' => 'Problem-Ref.',
    'Class:OrderRequest/Attribute:parent_change_id'   => 'Zugehörige Änderung',
    'Class:OrderRequest/Attribute:parent_change_ref'  => 'Änderungs-Ref.',
    'Class:OrderRequest/Attribute:related_request_list' => 'Abhängige Serviceanfragen',
    'Class:OrderRequest/Attribute:related_request_list+' => 'Serviceanfragen, die auf diese BANF verweisen.',

    'Class:OrderRequest/Stimulus:ev_submit'           => 'Einreichen',
    'Class:OrderRequest/Stimulus:ev_review'           => 'Prüfung starten',
    'Class:OrderRequest/Stimulus:ev_request_approval' => 'Genehmigung anfordern',
    'Class:OrderRequest/Stimulus:ev_approve'          => 'Genehmigen',
    'Class:OrderRequest/Stimulus:ev_reject'           => 'Ablehnen',
    'Class:OrderRequest/Stimulus:ev_procure'          => 'An Beschaffung übergeben',
    'Class:OrderRequest/Stimulus:ev_close'            => 'Schließen',

    'Class:OrderRequest/Error:AtLeastOneLineItemBeforeSubmit' => 'Bitte fügen Sie vor dem Einreichen mindestens eine Position hinzu.',
));

// ─────────────────────────────────────────────────────────────────────────────
// Klasse: OrderRequestLineItem
// ─────────────────────────────────────────────────────────────────────────────
/** @disregard P1009 Undefined type Dict */
Dict::Add('DE DE', 'German', 'Deutsch', array(
    'OrderRequestLineItem:base'        => 'Grunddaten',
    'OrderRequestLineItem:qtyprice'    => 'Menge & Preis',
    'OrderRequestLineItem:description' => 'Beschreibung',

    'Class:OrderRequestLineItem' => 'BANF-Position',
    'Class:OrderRequestLineItem/Plural' => 'BANF-Positionen',
    'Class:OrderRequestLineItem+' => 'Einzelne Position einer internen Bestellanforderung (BANF) für die IT-Beschaffung.',

    'Class:OrderRequestLineItem/Attribute:name'          => 'Bezeichnung',
    'Class:OrderRequestLineItem/Attribute:name+'         => 'Kurze Bezeichnung des Artikels/der Dienstleistung.',

    'Class:OrderRequestLineItem/Attribute:order_request_id'   => 'Bestellanforderung',
    'Class:OrderRequestLineItem/Attribute:order_request_id+'  => 'Zugehörige Bestellanforderung (BANF).',
    'Class:OrderRequestLineItem/Attribute:order_request_ref'  => 'BANF-Referenz',
    'Class:OrderRequestLineItem/Attribute:order_request_ref+' => 'Lesbare Referenz der zugehörigen BANF.',

    'Class:OrderRequestLineItem/Attribute:line_number'   => 'Pos.-Nr.',
    'Class:OrderRequestLineItem/Attribute:line_number+'  => 'Laufende Positionsnummer innerhalb der BANF.',

    'Class:OrderRequestLineItem/Attribute:vendor_sku'    => 'Lieferanten-Artikel-Nr.',
    'Class:OrderRequestLineItem/Attribute:vendor_sku+'   => 'Lieferantenspezifische Artikelnummer (optional).',

    'Class:OrderRequestLineItem/Attribute:description'   => 'Beschreibung',
    'Class:OrderRequestLineItem/Attribute:description+'  => 'Optionale Details für Beschaffung/IT.',

    'Class:OrderRequestLineItem/Attribute:quantity'      => 'Menge',
    'Class:OrderRequestLineItem/Attribute:quantity+'     => 'Angeforderte Menge.',

    'Class:OrderRequestLineItem/Attribute:uom'           => 'Mengeneinheit',
    'Class:OrderRequestLineItem/Attribute:uom+'          => 'Kaufmännische Mengeneinheit der Position.',
    'Class:OrderRequestLineItem/Attribute:uom/Value:EA'  => 'Stück',
    'Class:OrderRequestLineItem/Attribute:uom/Value:PT'  => 'Personentag',
    'Class:OrderRequestLineItem/Attribute:uom/Value:HUR' => 'Stunde',
    'Class:OrderRequestLineItem/Attribute:uom/Value:LIC' => 'Lizenz',
    'Class:OrderRequestLineItem/Attribute:uom/Value:SET' => 'Set',
    'Class:OrderRequestLineItem/Attribute:uom/Value:MON' => 'Monat',
    'Class:OrderRequestLineItem/Attribute:uom/Value:ANN' => 'Jahr',

    'Class:OrderRequestLineItem/Attribute:unit_price_estimated'  => 'Geschätzter Einzelpreis',
    'Class:OrderRequestLineItem/Attribute:unit_price_estimated+' => 'Geschätzter Preis pro Einheit.',
    'Class:OrderRequestLineItem/Attribute:total_price_estimated'  => 'Geschätzter Gesamtpreis',
    'Class:OrderRequestLineItem/Attribute:total_price_estimated+' => 'Automatisch berechnet: Menge × geschätzter Einzelpreis.',

    // Validierungen (in PHP verwendet)
    'Class:OrderRequestLineItem/Error:ParentNotEditable'      => 'Diese Position kann nicht geändert werden, da die zugehörige Bestellanforderung nicht mehr im Status „Entwurf“ ist.',
    'Class:OrderRequestLineItem/Error:QtyMustBePositive'      => 'Die Menge muss größer als 0 sein.',
    'Class:OrderRequestLineItem/Error:UnitPriceNegative'      => 'Der geschätzte Einzelpreis darf nicht negativ sein.',
    'Class:OrderRequestLineItem/Error:UomRequired'            => 'Bitte eine Mengeneinheit auswählen.',
    'Class:OrderRequestLineItem/Warning:DuplicateNameUom'     => 'Es existiert bereits eine Position mit gleicher Bezeichnung und Mengeneinheit.',
));
