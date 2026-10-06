<?php

use App\Models\TicketOrder;

/*
 * Afwijkingen van El Pablo op de standaardconfig van webgoeroe/seo-growth.
 * Enkel wat hier staat wijkt af; de rest komt uit de package.
 */
return [

    // Een betaalde ticketbestelling telt als conversie op Groei → Leads.
    'lead_types' => [
        TicketOrder::LEAD_TYPE => 'Ticketaankoop',
    ],

];
