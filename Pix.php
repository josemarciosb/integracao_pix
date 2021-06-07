<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pix extends Model
{
    /**
     * IDs do Payload do Pix
     * @var string
     */
    const ID_PAYLOAD_FORMAT_INDICATOR = '00';
    const ID_MERCHANT_ACCOUNT_INFORMATION = '26';
    const ID_MERCHANT_ACCOUNT_INFORMATION_GUI = '00';
    const ID_MERCHANT_ACCOUNT_INFORMATION_KEY = '01';
    const ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION = '02';
    const ID_MERCHANT_CATEGORY_CODE = '52';
    const ID_TRANSACTION_CURRENCY = '53';
    const ID_TRANSACTION_AMOUNT = '54';
    const ID_COUNTRY_CODE = '58';
    const ID_MERCHANT_NAME = '59';
    const ID_MERCHANT_CITY = '60';
    const ID_ADDITIONAL_DATA_FIELD_TEMPLATE = '62';
    const ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID = '05';
    const ID_ADDITIONAL_DATA_FIELD_TEMPLATE_GUI = '503000';
    const ID_ADDITIONAL_DATA_FIELD_TEMPLATE_VERSION = '01';
    const ID_CRC16 = '63';

    /**
     * Chave pix
     * @var string
     */
    private $pixKey;


    /**
     * Descrição pagamento
     * @var string
     */
    private $description;


    /**
     * Nome titular da conta
     * @var string
     */
    private $merchantName;


    /**
     * Cidade titular da conta
     * @var string
     */
    private $merchantCity;


    /**
     * ID transação
     * @var string
     */
    private $txid;


    /**
     * Valor transação
     * @var string
     */
    private $amount;


    /**
     * método responsável por definir o valor de $pixkey
     * @param string
     */
    public function setPixKey($pixKey)
    {
        $this->pixKey = $pixKey;
        return $this;
    }

    /**
     * método responsável por definir o valor de $description
     * @param string
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * método responsável por definir o valor de $merchantname
     * @param string
     */
    public function setMerchantName($merchantName)
    {
        $this->merchantName = $merchantName;
        return $this;
    }

    /**
     * método responsável por definir o valor de $merchantcity
     * * @param string
     */
    public function setMerchantCity($merchantCity)
    {
        $this->merchantCity = $merchantCity;
        return $this;
    }

    /**
     * método responsável por definir o valor de $txid
     * * @param string
     */
    public function setTxid($txid)
    {
        $this->txid = $txid;
        return $this;
    }

    /**
     * método responsável por definir o valor de $amount
     * * @param float
     */
    public function setAmount($amount)
    {
        $this->amount = (string)number_format($amount, 2, '.', '');
        return $this;
    }

    /**
     * método responsável por retornar o valor completo de um objeto pix
     * * @param string
     * * @param string
     * * @return string
     */
    private function getValue($id, $value)
    {
        $size = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);
        return $id . $size . $value;
    }

    /**
     * método responsável por retornar os valores completos da informação da conta
     * * @return string
     */
    private function getMerchantAccountInformation()
    {
        $gui = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_GUI, 'br.gov.bcb.pix');

        $key = $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_KEY, $this->pixKey);

        $description = strlen($this->description) ? $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION, $this->description) : '';

        return $this->getValue(self::ID_MERCHANT_ACCOUNT_INFORMATION, $gui . $key . $description);
    }


    /**
     * método responsável por retornar os valores completos do campo adicional do pix (txid)
     * * @return string
     */
    public function getAdditionalDataFieldTemplate()
    {
        $txid = $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TXID, $this->txid);
        $tgui = $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_GUI, 'BR.GOV.BCB.BRCODE');
        $tversion = $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_VERSION, '1.0.0');

        return $this->getValue(self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE, $txid.$tgui.$tversion);
    }

    /**
     * método responsável por gerar qrcode
     * * @param string
     */
    public function getPayload()
    {
        $payload = $this->getValue(self::ID_PAYLOAD_FORMAT_INDICATOR, '01') .
            $this->getMerchantAccountInformation() .
            $this->getValue(self::ID_MERCHANT_CATEGORY_CODE, '0000') .
            $this->getValue(self::ID_TRANSACTION_CURRENCY, '986') .
            $this->getValue(self::ID_TRANSACTION_AMOUNT, $this->amount) .
            $this->getValue(self::ID_COUNTRY_CODE, 'BR') .
            $this->getValue(self::ID_MERCHANT_NAME, $this->merchantName) .
            $this->getValue(self::ID_MERCHANT_CITY, $this->merchantCity) .
            $this->getAdditionalDataFieldTemplate();

        return $payload.$this->getCRC16($payload);
    }

    /**
     * Método responsável por calcular o valor da hash de validação do código pix
     * @return string
     */
    private function getCRC16($payload)
    {
        //ADICIONA DADOS GERAIS NO PAYLOAD
        $payload .= self::ID_CRC16 . '04';

        //DADOS DEFINIDOS PELO BACEN
        $polinomio = 0x1021;
        $resultado = 0xFFFF;

        //CHECKSUM
        if (($length = strlen($payload)) > 0) {
            for ($offset = 0; $offset < $length; $offset++) {
                $resultado ^= (ord($payload[$offset]) << 8);
                for ($bitwise = 0; $bitwise < 8; $bitwise++) {
                    if (($resultado <<= 1) & 0x10000) $resultado ^= $polinomio;
                    $resultado &= 0xFFFF;
                }
            }
        }

        //RETORNA CÓDIGO CRC16 DE 4 CARACTERES
        return self::ID_CRC16 . '04' . strtoupper(dechex($resultado));
    }


}
