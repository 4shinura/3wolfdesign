<?php
// tests/Unit/PaypalPriceTest.php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

class PaypalPriceTest extends TestCase
{
    public function testPaypalAmountExtraction(): void
    {
        // 1. On simule le contenu de ton fichier payment.json
        // (Tu peux aussi utiliser file_get_contents('payment.json') si le fichier est à la racine)
        $json = file_get_contents('C:\laragon\www\3wolfdesign\tests\paypal-capture.json');

        // 2. On décode comme dans ton service (en tableau associatif)
        $result = json_decode($json, true);

        // Debug : si c'est null, le JSON est mal formé
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail("Erreur de syntaxe JSON : " . json_last_error_msg());
        }

        // On tente l'extraction étape par étape pour identifier où ça casse
        $this->assertArrayHasKey('purchase_units', $result, "Clé 'purchase_units' manquante");
        $this->assertArrayHasKey('payments', $result['purchase_units'][0], "Clé 'payments' manquante");
        $this->assertArrayHasKey('captures', $result['purchase_units'][0]['payments'], "Clé 'captures' manquante");

        $amountPaid = $result['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? null;

        $this->assertNotNull($amountPaid, "Le montant est toujours nul après extraction.");
        $this->assertEquals("77.97", $amountPaid);
    }
}