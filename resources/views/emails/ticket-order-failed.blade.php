<!DOCTYPE html>
<html lang="nl">
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin: 0 0 0.5rem; color: #b91c1c;">Ticketbestelling niet verwerkt</h2>
    <p style="margin: 0 0 1rem;">
        Een betaling kwam binnen via Stripe, maar het aanmaken/afronden van de
        bijhorende ticketbestelling is mislukt. Stripe blijft de webhook opnieuw
        proberen; kijk in de logs en in Filament (Events → Bestellingen) of het
        intussen goed kwam.
    </p>
    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-size: 14px;">
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <th align="left" style="padding-right: 1rem;">Referentie (uuid)</th>
            <td>{{ $uuid }}</td>
        </tr>
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <th align="left" style="padding-right: 1rem;">Foutmelding</th>
            <td style="white-space: pre-wrap;">{{ $error }}</td>
        </tr>
    </table>
</body>
</html>
