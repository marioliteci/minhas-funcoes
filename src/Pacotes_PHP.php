<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MarioLiteci\MinhasFuncoes;

use Aws;



/**
 * Description of AWS_S3
 *
 * @author Usuario
 */
class Pacotes_PHP {

    const key = '';
    const secret = '';
    const bucket = '';
    const bucket_documentos = '';
    const bucket_pix = '';
    const buckettmp = '';
    const key_sms = '';
    const key_sms_secret = '';
    const bucket_qrcodeimg_pix = '';

    function envio_sms($phone, $message) {

        $credentials = new \Aws\Credentials\Credentials(self::key_sms, self::key_sms_secret);

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

    function gravar_imagem_abertura_conta($cnh, $rgfrente, $rgverso, $comp_residencia, $cartao_cnpj) {
        
    }

    function download_bucket($cnpj, $mesano, $modelo, $proprio_S_N) {
        $credentials = new Aws\Credentials\Credentials(self::key, self::secret);
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
        $credentials = new Aws\Credentials\Credentials(self::key, self::secret);
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
        $s3Client->downloadBucket($diretorio_local, self::buckettmp, $diretorio_bucket);
    }

    function download_arquivo($arquivo) {
        if (substr($arquivo, 0, 1) == '/') {
            $arquivo = substr($arquivo, 1);
        }
        $credentials = new Aws\Credentials\Credentials(self::key, self::secret);
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
        $credentials = new Aws\Credentials\Credentials(self::key, self::secret);
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
            $ret = $this->enviar_arquivo_aws(self::bucket, $nome_arquivo, $file, false);
        }
        return $ret;
    }

    function enviar_arquivo_tmp($file) {
        $ret = '';
        if (file_exists($file)) {
            $array = explode('/', $file);
            $nome_arquivo = $array[count($array) - 1];
            $destino_aws = $nome_arquivo;
            $ret = $this->enviar_arquivo_aws(self::buckettmp, $destino_aws, $file, false);
        }
        return $ret;
    }

    function enviar_arquivo_pix_img_qrcode($file) {
        $ret = '';
        if (file_exists($file)) {
            $array = explode('/', $file);
            $nome_arquivo = $array[count($array) - 1];
            $destino_aws = 'qrcodeimg/' . $nome_arquivo;
            $ret = $this->enviar_arquivo_aws(self::bucket_qrcodeimg_pix, $destino_aws, $file, true);
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
            $ret = $this->enviar_arquivo_aws(self::bucket_documentos, $destino_aws, $file, false);
        }
        return $ret;
    }

    function download_arquivo_documento_cliente($arquivo, $destino) {
        return $this->download_arquivo_aws(self::bucket_documentos, $arquivo, $destino);
    }

    private function enviar_arquivo_aws($bucket, $destino_aws, $file, $publico = true) {
        if (file_exists($file)) {

            $credentials = new Aws\Credentials\Credentials(self::key, self::secret);
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
            $credentials = new Aws\Credentials\Credentials(self::key, self::secret);
            $s3Client = new Aws\S3\S3Client([
                'version' => 'latest',
                'region' => 'sa-east-1',
                'credentials' => $credentials,
            ]);
            $orig = 'cliente/04068400407/'.basename($origem_aws);
            
            $s3Client->getObject([
                'Bucket' => $bucket_aws,
                'Key' => $orig,
                'SaveAs' => $destino_local,
            ]);
            
            return $destino_local;
        } catch (\Exception $ex) {
            $objController->ReturnFailValidation(__FUNCTION__.' - '.$ex->getMessage());
        }
    }

}
