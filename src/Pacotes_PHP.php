<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MarioLiteci\MinhasFuncoes;

use Aws;

class Pacotes_PHP {
    private static $key;
    private static $secret;
    private static $bucket = '';
    private static $bucket_documentos = '';
    private static $bucket_pix = '';
    private static $buckettmp = '';
    private static $key_sms = '';
    private static $key_sms_secret = '';
    private static $bucket_qrcodeimg_pix = '';
    public function __construct() {
        $diretorio='/home/ubuntu/liteci_config.txt';
        $dados = file_get_contents($diretorio);
        $arq = json_decode($dados);
        $this->name_key_user=$arq->key_user_mario;
        $this->name_key_secret=$arq->key_user_mario;
        $this->bucket=$arq->name_bucket_principal;
        $this->bucket_documentos=$arq->name_bucket_documentos;
        $this->bucket_pix=$arq->name_bucket_pix;
        $this->buckettmp=$arq->name_bucket_tmp;
        $this->key_sms=$arq->name_key_sms;
        $this->key_sms_secret=$arq->name_secret_sms;
        $this->bucket_qrcodeimg_pix=$arq->name_bucket_qrcodeimg_pix;
    }

    function envio_sms($phone, $message) {

        $credentials = new \Aws\Credentials\Credentials(self::$key_sms, self::$key_sms_secret);

        $SnSclient = new SnsClient([
            'region' => 'us-west-2',
            'version' => '2010-03-31',
            'credentials' => $credentials,
        ]);
        $phone = '+55' . $phone;
        try {
            $result = $SnSclient->publish([
                'Message' => $message,
                'PhoneNumber' => $phone,
            ]);
        } catch (\Exception $e) {
            // output error message if fails
            error_log($e->getMessage());
        }
    }

    function download_bucket($cnpj, $mesano, $modelo, $proprio_S_N) {
        $credentials = new Aws\Credentials\Credentials(self::$key, self::$secret);
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'sa-east-1',
            'credentials' => $credentials,
        ]);
        $objvalidacao = new \apimontaxml\lib\ValidacoesUteis();
        $diretorio_bucket = $objvalidacao->get_diretorio_gravacao_xml($cnpj, $modelo, $mesano, $proprio_S_N);  // $objvalidacao->Get_Diretorio_Raiz() . "/Api/arquivos/xml/" . $cnpj . "/" . $mesano;
        if (substr($diretorio_bucket, 0, 1) == '/') {
            $diretorio_bucket = substr($diretorio_bucket, 1);
        }
        $diretorio_local = "download_backup/" . $cnpj . "/" . $mesano;
        $s3Client->downloadBucket($diretorio_local, self::bucket, $diretorio_bucket);
    }

    function download_bucket_tmp($cnpj, $mesano, $modelo, $proprio_S_N) {
        $credentials = new Aws\Credentials\Credentials(self::$key, self::$secret);
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'sa-east-1',
            'credentials' => $credentials,
        ]);
        $objvalidacao = new \apimontaxml\lib\ValidacoesUteis();
        $diretorio_bucket = $objvalidacao->get_diretorio_gravacao_xml($cnpj, $modelo, $mesano, $proprio_S_N);  // $objvalidacao->Get_Diretorio_Raiz() . "/Api/arquivos/xml/" . $cnpj . "/" . $mesano;
        if (substr($diretorio_bucket, 0, 1) == '/') {
            $diretorio_bucket = substr($diretorio_bucket, 1);
        }
        $diretorio_local = "download_backup/" . $cnpj . "/" . $mesano;
        $s3Client->downloadBucket($diretorio_local, self::$buckettmp, $diretorio_bucket);
    }

    function download_arquivo($arquivo) {
        if (substr($arquivo, 0, 1) == '/') {
            $arquivo = substr($arquivo, 1);
        }
        $credentials = new Aws\Credentials\Credentials(self::$key, self::$secret);
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'sa-east-1',
            'credentials' => $credentials,
        ]);


        $diretorio = "/var/www/html/dfe/Api/download_backup/" . basename($arquivo);
        $s3Client->getObject([
            'Bucket' => self::bucket,
            'Key' => $arquivo,
            'SaveAs' => $diretorio,
        ]);

        return $diretorio;
    }

    function download_arquivo_tmp($arquivo) {
        $arquivo = trim($arquivo);
        if (substr($arquivo, 0, 1) == '/') {
            $arquivo = substr($arquivo, 1);
        }
        $credentials = new Aws\Credentials\Credentials(self::$key, self::$secret);
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'sa-east-1',
            'credentials' => $credentials,
        ]);
        $diretorio = "/var/www/html/dfe/Api/download_backup/" . basename($arquivo);
        $s3Client->getObject([
            'Bucket' => self::buckettmp,
            'Key' => $arquivo,
            'ResponseContentType' => 'application/zip',
            'SaveAs' => $diretorio,
        ]);

        return $diretorio;
    }

    function enviar_arquivo($file) {
        $ret = '';
        if (file_exists($file)) {
            //aqui estou tirando os pontos iniciais que vem no arquivo ../
            if (substr($file, 0, 1) == '/') {
                $nome_arquivo = substr($file, 1); //  substr($file, 3); //  $file;// '07840348000136/teste/'.basename( $file );
            } else {
                $nome_arquivo = $file;
            }
            $ret = $this->enviar_arquivo_aws(self::$bucket, $nome_arquivo, $file, false);
        }
        return $ret;
    }

    function enviar_arquivo_tmp($file) {
        $ret = '';
        if (file_exists($file)) {
            $array = explode('/', $file);
            $nome_arquivo = $array[count($array) - 1];
            $destino_aws = $nome_arquivo;
            $ret = $this->enviar_arquivo_aws(self::$buckettmp, $destino_aws, $file, false);
        }
        return $ret;
    }

    function enviar_arquivo_pix_img_qrcode($file) {
        $ret = '';
        if (file_exists($file)) {
            $array = explode('/', $file);
            $nome_arquivo = $array[count($array) - 1];
            $destino_aws = 'qrcodeimg/' . $nome_arquivo;
            $ret = $this->enviar_arquivo_aws(self::$bucket_qrcodeimg_pix, $destino_aws, $file, true);
        }
        return $ret;
    }

    function excluir_arquivo_diretorio_backup_local($cnpj) {
        $path = '/var/www/html/dfe/Api/download_backup/' . $cnpj . '/';
        // Muda para o diretório
        chdir($path);
        // Executa o comando sob o diretório
        exec('ls | xargs rm -rf');
        // Apaga a pasta que deve estar vazia, nesse ponto.
        rmdir($path);
    }

    function enviar_arquivo_doc_clientes($cpf, $file) {
        $ret = '';
        if (file_exists($file)) {
            $array = explode('/', $file);
            $nome_arquivo = $array[count($array) - 1];
            $destino_aws = 'cliente/' . $cpf . '/' . $nome_arquivo;
            $ret = $this->enviar_arquivo_aws(self::$bucket_documentos, $destino_aws, $file, false);
        }
        return $ret;
    }

    function download_arquivo_documento_cliente($arquivo, $destino) {
        return $this->download_arquivo_aws(self::$bucket_documentos, $arquivo, $destino);
    }

    private function enviar_arquivo_aws($bucket, $destino_aws, $file, $publico = true) {
        if (file_exists($file)) {

            $credentials = new Aws\Credentials\Credentials(self::$key, self::$secret);
            $s3Client = new Aws\S3\S3Client([
                'version' => 'latest',
                'region' => 'sa-east-1',
                'credentials' => $credentials,
            ]);

            if ($publico == true) {
                $response = $s3Client->putObject([
                    'Bucket' => $bucket,
                    'Key' => $destino_aws,
                    'SourceFile' => $file,
                    'ACL' => 'public-read',
                ]);
            } else {
                $response = $s3Client->putObject([
                    'Bucket' => $bucket,
                    'Key' => $destino_aws,
                    'SourceFile' => $file,
                ]);
            }
            $retorno = $response['ObjectURL'];
            return $retorno;
        }
    }

    private function download_arquivo_aws($bucket_aws, $origem_aws, $destino_local) {
        try {
            $objController = new ControllerApi();
            $credentials = new Aws\Credentials\Credentials(self::$key, self::$secret);
            $s3Client = new Aws\S3\S3Client([
                'version' => 'latest',
                'region' => 'sa-east-1',
                'credentials' => $credentials,
            ]);
            $orig = 'cliente/04068400407/' . basename($origem_aws);

            $s3Client->getObject([
                'Bucket' => $bucket_aws,
                'Key' => $orig,
                'SaveAs' => $destino_local,
            ]);

            return $destino_local;
        } catch (\Exception $ex) {
            $objController->ReturnFailValidation(__FUNCTION__ . ' - ' . $ex->getMessage());
        }
    }

    public static function ValidaDatas($Dia, $Mes, $Ano) {

        $DiaV = true;
        $MesV = true;
        $Anov = true;

        $Array31 = array('1', '3', '5', '7', '8', '10', '12');
        $Array30 = array('4', '6', '9', '11');

        if (in_array($Mes, $Array31)) {
            if ($Dia < 1 || $Dia > 31):
                $DiaV = false;
            endif;
        }

        elseif (in_array($Mes, $Array30)) {
            if ($Dia < 1 || $Dia > 30):
                $DiaV = false;
            endif;
        }

        elseif ($Mes == 2) {
            if (($Ano % 4 == 0 && $Ano % 100 != 0) || ($Ano % 400 == 0)) {
                $Fev = 29;
            } else {
                $Fev = 28;
            }

            if ($Dia < 1 || $Dia > $Fev):
                $DiaV = false;
            endif;
        }
        else {
            $MesV = false;
        }


        if (!$MesV) {
            $DataValida = FALSE;
        } elseif (!$DiaV) {
            $DataValida = FALSE;
        } else {
            $DataValida = TRUE;
        }
        return $DataValida;
    }

    public static function ConverteDatasBrazil($Data) {
        list($Dia, $Mes, $Ano) = $this->QuebraDatas($Data);
        if (strlen($Ano) == 4 AND $Mes <= 12 AND $Dia <= 31) {
            $DataValida = $this->ValidaDatas($Dia, $Mes, $Ano);
            if ($DataValida == FALSE) {
                $Retorno = False;
            } else {
                $Retorno = $Dia . '/' . $Mes . '/' . $Ano;
            }
        } else {
            $Retorno = False;
        }
        return $Retorno;
    }

    static public function Get_Data_hoje_acrescentando_dias_formato_USA($dias) {
        $data = strtotime('+' . $dias . ' days');
        return date("Y-m-d", $data);
    }

    static public function Get_Data_acrescentando_dias_formato_USA($data, $dias) {
        $dt = strtotime('+' . $dias . ' days', $data);
        return date("Y-m-d", $dt);
    }

    static public function Get_hora_agora_acrescentando_minutos($minutos) {
        $data = strtotime('+' . $minutos . ' minutes');
        return date("H:i:00", $data);
    }

    static public function Get_Data_hora_agora_acrescentando_minutos($minutos) {
        $data = strtotime('+' . $minutos . ' minutes');
        return date("Y-m-d H:i:00", $data);
    }

    static public function get_dias_entre_datas($date1, $date2) {
        $data_inicio = new \DateTime($date1);
        $data_fim = new \DateTime($date2);
        $dateInterval = $data_inicio->diff($data_fim);
        return $dateInterval->days;
    }

    static public function get_diferenca_entre_segundos($datetime1, $datetime2) {
        $data_inicio = new \DateTime($datetime1);
        $data_fim = new \DateTime($datetime2);
        $dateInterval = $data_inicio->diff($data_fim);
        $minutes = $dateInterval->days * 24 * 60;

        $minutes += $dateInterval->h * 60;
        $minutes += $dateInterval->i;
        $segundos = $minutes * 60;
        $segundos += $dateInterval->s;

        return $segundos;
    }

    static public function get_diferenca_entre_minutos($datetime1, $datetime2) {
        $data_inicio = new \DateTime($datetime1);
        $data_fim = new \DateTime($datetime2);
        $dateInterval = $data_inicio->diff($data_fim);
        $minutes = $dateInterval->days * 24 * 60;
        $minutes += $dateInterval->h * 60;
        $minutes += $dateInterval->i;
        return $minutes;
    }

    public static function Get_Data_Formato_Agora_USA() {
        $agora = time();
        return date("Y-m-d", $agora);
    }

    public static function Get_Data_Hora_Formato_USA_time($time) {
        $agora = $time;
        return date("Y-m-d H:i:s", $agora);
    }

    public static function Get_Data_Formato_Agora_BR() {
        $agora = time();
        return date("d/m/Y", $agora);
    }

    public static Function Get_Data_Converter_Data_USA($data) {
        $data = str_replace("/", '-', $data);
        $datanew = strtotime($data);
        return date("Y-m-d", $datanew);
    }

    public static Function Get_Data_Converter_Data_USA_Time($Time) {
        //$data=    str_replace("/", '-', $data);
        //$datanew =  strtotime($data);
        return date("Y-m-d", $Time);
    }

    public static Function Get_Data_Hora_Converter_Data_USA($data) {
        $data1 = substr($data, 0, 10);
        $hora = substr($data, 11, 10);
        $ret = explode('/', $data1);
        return $datanew = $ret[2] . "-" . $ret[1] . "-" . $ret[0] . " " . $hora;
    }

    public static function Get_Data_Hora_Formato_USA() {
        $agora = time();
        return date("Y-m-d H:i:s", $agora);
    }

    public static function Get_Data_Hora_Formato_BR() {
        $agora = time();
        return date("d-m-Y H:i:s", $agora);
    }

    public static function Get_Data_Milesegundos() {
        return round(microtime(true) * 1000);
    }

    public static function Get_Hora_Agora() {
        $agora = time();
        return date("H:i:s", $agora);
    }

    public static Function Get_Data_Converter_Data_BR($data_USA) {
        if ($data_USA != NULL) {
            $data = str_replace("/", '-', $data_USA);
            $datanew = strtotime($data_USA);
            return date("d/m/Y", $datanew);
        } else {
            return NULL;
        }
    }

    public static Function Get_Data_Inicial_Mes_USA() {
        $mes = date('Y/m', time());
        return $mes . '/01';
    }

}
