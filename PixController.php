<?php

namespace App\Http\Controllers;

use App\Models\Pix;
use Illuminate\Http\Request;

use Mpdf\QrCode\QrCode;
use Mpdf\QrCode\Output\Png;
use function Psy\debug;

class PixController extends Controller
{

    public static function generatePix()
    {

        $pix = new Pix();

        $pix->setPixKey('e1e52ae9-b5c5-43c9-ad17-0f3c785ae3c6');
        $pix->setDescription('sua descrição');
        $pix->setMerchantName('seu nome');
        $pix->setMerchantCity('sua cidade');
        $pix->setAmount('valor cobrado');
        $pix->setTxid('***');

        $payload = $pix->getPayload();

        $qrCode = new QrCode($payload);

        $output = (new Png)->output($qrCode, 300);

        return [
            'qrCode' => $output,
            'payload' => $payload,
        ];
    }
}
