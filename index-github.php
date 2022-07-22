<?PHP
include_once __DIR__ . '/vendor/autoload.php';

$appnome = 'Google API PHP';
$title = 'MyDoc-AutoCreated';
$credfile = 'auth-credentials.json';

//define result2 array for main html if necessary
$result2 = [];

//recebe/inicia os vars do html para configurar o texto a ser inserido no documento
$texto= $_POST['texto'];
$bold=$_POST['bold'];
$underline=$_POST['underline'];
$alignment=$_POST['alignment'];
$italic=$_POST['italic'];
$bkred= (number_format((float)$_POST['bkred'], 1, '.', '')/10);
$bkgreen= (number_format((float)$_POST['bkgreen'], 1, '.', '')/10);
$bkblue= (number_format((float)$_POST['bkblue'], 1, '.', '')/10);
$fgred= (number_format((float)$_POST['fgred'], 1, '.', '')/10);
$fggreen= (number_format((float)$_POST['fggrern'], 1, '.', '')/10);
$fgblue= (number_format((float)$_POST['fgblue'], 1, '.', '')/10);
$fontsize=$_POST['fontsize'];
$fontfamily= $_POST['fontfamily'];
$criaarquivo=$_POST['criaarquivo'];
$idarquivo=$_POST['idarquivo'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    print_r($_POST);
    echo '<hr>';
}

/*************************************************
 * Ensure you've downloaded your oauth credentials
 ************************************************/
if (!$oauth_credentials = getOAuthCredentialsFile($credfile)) {
    echo missingOAuth2CredentialsWarning($credfile);
    return;
}

/***************************************************
 * The redirect URI is to the current page, e.g:
 * ex: http://localhost:8080/simple-file-upload.php
 ***************************************************/
$redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

//init Google Api Client to authentication ($client)
$client = new Google\Client();
//$client->setAuthConfig($oauth_credentials);
$client->setApplicationName($appnome);
$client->setScopes('https://www.googleapis.com/auth/documents      ');
$client->addScope('https://www.googleapis.com/auth/spreadsheets');
$client->addScope('https://www.googleapis.com/auth/drive');
try {
    $client->setAuthConfig("$credfile");
} catch (\Google\Exception $e) {
    print('Error!! Exception catch:'.$e.'<br><br>');
    exit;
}
$client->setRedirectUri($redirect_uri);
$client->setAccessType('online');

$service = new Google\Service\Drive($client);

print(pageHeader($appnome));

/************************************************
 * If we have a code back from the OAuth 2.0 flow,
 * we need to exchange that with the
 * Google\Client::fetchAccessTokenWithAuthCode()
 * function. We store the resultant access token
 * bundle in the session, and redirect to ourself.
 ************************************************/
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // store in the session also
    $_SESSION['upload_token'] = $token;

    // redirect back
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}
// set the access token as part of the client
if (!empty($_SESSION['upload_token'])) {
    $client->setAccessToken($_SESSION['upload_token']);
    if ($client->isAccessTokenExpired()) {
        unset($_SESSION['upload_token']);
    }
} else {
    $authUrl = $client->createAuthUrl();
}

// add "?logout" to the URL to remove a token from the session
if (isset($_REQUEST['logout'])) {
    unset($_SESSION['upload_token']);
    ?>
    <div class="request">
        <a autofocus style="size: auto;alignment: center;background-color: #195f91;color: #FFFFFF" class='login' href='<?= $authUrl ?>'>Autorizar e/ou Connectar ao Google Docs!</a>
    </div>
    <?php
    exit;
}


/************************************************
 * If we're signed in then lets try to create our
 * file.
 ************************************************/
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $client->getAccessToken()) {
    //localiza o id do arquivo, seja criando um ou recebendo o arquivoid informado
    if (getarqid($client,$criaarquivo,$idarquivo,$title,$service)) {
        //se resp =true entao cria arquivo nao usa o id
        $file = criaarquivo($client, $title, $service);
        $fileId = $file->getId();
    } else {
        //se resp = false usa o iddoarquivo q vem do post
        $fileId=$idarquivo;
    }
    $result2 += ['id' => $fileId];
    $result2 += ['name' => $title];
    InserirTexto ($client,$texto,$bkred,$bkgreen,$bkblue,$fgred,$fggreen,$fgblue,$fontsize,$fontfamily,$bold,$italic,$underline,$alignment,$fileId);
}

?>
    <div class="box">
        <?php if ( (isset($authUrl)) ) : ?>
            <div class="request">
                <a autofocus style="size: auto;alignment: center;background-color: #195f91;color: #FFFFFF" class='login' href='<?= $authUrl ?>'>Autorizar e/ou Connectar ao Google Docs!</a>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST') : ?>
            <div class="shortened">
                <p>Veja no Drive o Arquivo:
                    <?php //showopcoeshtml($texto,$bkred,$fgred,$bkgreen,$fggreen,$bkblue,$fgblue,$fontsize,$fontfamily,$bold,$italic,$underline,$alignment,$fileId,$criaarquivo); ?>
                </p>
                <ul>
                    <!--<li><a href="https://drive.google.com/open?id=< ? = $result->id ? >" target="_blank">< ? = $result->name ? ></a></li>-->
                    <li><a href="https://drive.google.com/open?id=<?= $result2['id'] ?>"
                           target="_blank"><?= $result2['name'] ?></a></li>
                </ul>
            </div>
        <?php endif ?>

        <form method="POST">
            <?php showopcoeshtml($texto,$bkred,$fgred,$bkgreen,$fggreen,$bkblue,$fgblue,$fontsize,$fontfamily,$bold,$italic,$underline,$alignment,$fileId,$criaarquivo); ?>
            <input type="submit" value="Click here to create a Doc in google drive!"/>
        </form>

    </div>
<?= pageFooter('<hr>'); ?>

<?php
exit; //fim do programa principal nao precisa prosseguir

function showopcoeshtml($texto,$bkred,$fgred,$bkgreen,$fggreen,$bkblue,$fgblue,$fontsize,$fontfamily,$bold,$italic,$underline,$alignment,$fileId,$criaarquivo) {
    ?>
    <p>
        <label>
            ID do Arquivo:&nbsp;
            <input type="text" name="idarquivo" value="<?= $fileId ?>">
        </label>
    </p>
    <p>
        <label>
            Criar Arquivo no Docs?&nbsp;
            <input aria-activedescendant="" type="checkbox" name="criaarquivo" value="yes" <?PHP
                echo 'unchecked';
            ?>>
        </label> <!--final do input criaarquivo -->
    </p>
    <!--<p><input type="text" name="texto" value="$texto"></p>>-->
    <p>
        <label>
            Negrito?&nbsp;
            <input type="checkbox" name="bold" value="yes" <?PHP
                if($bold or ($bold=='yes') or ($bold=='true')){
                    echo 'checked';
                } else {
                    echo 'unchecked';
                }
            ?>>
        </label> <!--final do input bold -->
    </p>
    <p>
        <label>
            Italico?&nbsp;
            <input type="checkbox" name="italic" value="yes" <?PHP
                if(($italic) or ($italic=='yes') or ($italic=='true')){
                    echo 'checked';
                } else {
                    echo 'unchecked';
                }
            ?>>
        </label> <!--final do input italic -->
    </p>
    <p>
        <label>
            Sublinhado?&nbsp;
            <input type="checkbox" name="underline" value="yes" <?PHP
                if(($underline) or ($underline=='yes') or ($underline=='true')){
                    echo 'checked';
                } else {
                    //se nao veio o underline vai false mesmo
                    echo 'unchecked';
                }
            ?>>
        </label> <!--final do input underline -->
    </p>
    <p>
        <label>
            Alinhamento:&nbsp;
            <input type="text" name="alignment" value="<?PHP
            if( (!is_null($alignment)) and (!empty($alignment)) ){
                echo $alignment;
            } else {
                //se nao veio o alinhamento correto, vai o padrao justificado mesmo
                echo 'JUSTIFIED';
            }
        ?>">
        </label> <!-- final do input do alignment-->
    </p>
    <p>
        <label>
            Tamanho do texto:&nbsp;
            <input type="text" name="fontsize" value="<?PHP
                if((!is_null($fontsize)) and (!empty($fontsize)) ){
                    echo $fontsize;
                } else {
                    //se nao veio o tamanho da fonte correta, vai o padrao 12 mesmo
                    echo '12';
                }
            ?>">
        </label> <!-- final do input do tamanho do texto-->
    </p>
    <p>
        <label>
            Tipo da Fonte:&nbsp;
            <input type="text" name="fontfamily" value="<?PHP
                if( (!is_null($fontfamily)) and (!empty($fontfamily)) ){
                    echo $fontfamily;
                } else {
                    //se nao veio a fonte correta, vai o padrao arial mesmo
                    //echo 'Arial';
                    echo 'Comic Sans MS';
                }
            ?>">
        </label>
    </p>
    <p>
        <label>
            Cor de Fundo:(todos de 0 até 10)<br><!--&nbsp;-->
            Red:&nbsp;
            <input aria-required="true" aria-autocomplete="both" aria-valuemax="10" aria-valuemin="0" type="number"  name="bkred" value="<?PHP
                if( (!is_null($bkred)) and (!empty($bkred)) ){
                    echo $bkred*10;
                } else {
                    //se nao veio a cor correta, vai o padrao 0.0 mesmo
                    echo '0';
                }
            ?>"> &nbsp;-&nbsp;
            Green:&nbsp;
            <input aria-required="true" aria-autocomplete="both" aria-valuemax="10" aria-valuemin="0" type="number"  name="bkgreen" value="<?PHP
            if( (!is_null($bkgreen)) and (!empty($bkgreen)) ){
                echo $bkgreen*10;
            } else {
                //se nao veio a cor correta, vai o padrao 0.0 mesmo
                echo '0';
            }
            ?>"> &nbsp;-&nbsp;
            Blue:&nbsp;
            <input aria-required="true" aria-autocomplete="both" aria-valuemax="10" aria-valuemin="0" type="number"  name="bkblue" value="<?PHP
            if( (!is_null($bkblue)) and (!empty($bkblue)) ){
                echo $bkblue*10;
            } else {
                //se nao veio a cor correta, vai o padrao 0.0 mesmo
                echo '0';
            }
            ?>">
        </label>
    </p>
    <p>
        <label>
            Cor do Texto: (todos de 0 até 10)<br><!--&nbsp;-->
            Red:&nbsp;
            <input aria-required="true" aria-autocomplete="both" aria-valuemax="10" aria-valuemin="0" type="number"  name="fgred" value="<?PHP
            if( (!is_null($fgred)) and (!empty($fgred)) ){
                echo $fgred*10;
            } else {
                //se nao veio a cor correta, vai o padrao 1.00 mesmo
                echo '10';
            }
            ?>"> &nbsp;-&nbsp;
            Green:&nbsp;
            <input aria-required="true" aria-autocomplete="both" aria-valuemax="10" aria-valuemin="0" type="number" name="fggreen" value="<?PHP
            if( (!is_null($fggreen)) and (!empty($fggreen)) ){
                echo $fggreen*10;
            } else {
                //se nao veio a cor correta, vai o padrao 1.00 mesmo
                echo '10';
            }
            ?>"> &nbsp;-&nbsp;
            Blue:&nbsp;
            <input aria-required="true" aria-autocomplete="both" aria-valuemax="10" aria-valuemin="0" type="number" name="fgblue" value="<?PHP
            if( (!is_null($fgblue)) and (!empty($fgblue)) ){
                echo $fgblue*10;
            } else {
                //se nao veio a cor correta, vai o padrao 1.00 mesmo
                echo '10';
            }
            ?>">
        </label>
    </p>
    <p>
        <label>
            &nbsp;
            <textarea aria-required="true" aria-multiline="true" name="texto">
                <?PHP
                    if(strlen($texto)>0){
                        echo "$texto";
                    } else {
                        echo "\nDigite aqui o texto a ser inserido no arquivo!!\n";
                    }
                ?>
            </textarea>
        </label>
    </p>
    <hr>

    <?php
}

function InserirTexto ($client,$texto,$bkred,$bkgreen,$bkblue,$fgred,$fggreen,$fgblue,$fontsize,$fontfamily,$bold,$italic,$underline,$alignment,$fileId): bool
{
    //initialize Google API for Google Docs and link to $client var that handles authentication
    $docServices = new Google_Service_Docs($client);
    if (($bold=='true') or ($bold=='yes') or ($bold==true)) {
        $bbold = true;
    } else {
        $bbold = false;
    }
    if (($italic=='true') or ($italic=='yes') or ($italic==true)){
        $iitalic = true;
    } else {
        $iitalic = false;
    }
    if (($underline=='true') or ($underline=='yes') or ($underline==true)) {
        $uunderline = true;
    } else {
        $uunderline = false;
    }
    //set variables to insert text
    $indexins = 1;                                      //inicio do doc = 1
    $txtinserir = $texto;                               //old: "Sample1\n";
    $indexfim = (($indexins + (strlen($txtinserir)-1)));  //soma o indice inicial (1) com o length do texto para saber a pos do ultimo char
    //prepare the GDoc Request Array to do BatchUpdate of the commands, insert text, update styles, etc...
    $requests = [
        new Google_Service_Docs_Request(array(
            'insertText' => [
                'text' => "$texto",
                'location' => [
                    'index' => $indexins
                ]
            ]
        )),
        new Google_Service_Docs_Request(array(
            'updateTextStyle' => [
                'textStyle' => [
                    'italic' => $iitalic,
                    'bold' => $bbold,
                    'underline' => $uunderline,
                    'backgroundColor' => [ 'color' => [ 'rgbColor' => [ 'red' => number_format((float)$bkred, 1, '.', ''), 'green' => number_format((float)$bkgreen, 1, '.', ''), 'blue' => number_format((float)$bkblue, 1, '.', '') ] ] ],
                    'foregroundColor' => [ 'color' => [ 'rgbColor' => [ 'red' => number_format((float)$fgred, 1, '.', ''), 'green' => number_format((float)$fggreen, 1, '.', ''), 'blue' => number_format((float)$fgblue, 1, '.', '') ] ] ],
                    'weightedFontFamily' => [
                        'fontFamily' => "$fontfamily",
                        'weight' => 400
                    ],
                    'fontSize' => [
                        'unit' => 'PT',
                        'magnitude' => $fontsize
                    ]
                ],
                'range' => ['startIndex' => $indexins, 'endIndex' => $indexfim],
                'fields' => '*'
            ]
        )),
        new Google_Service_Docs_Request(array(
            'updateParagraphStyle' => [
                'range' => ['startIndex' => $indexins, 'endIndex' => $indexfim],
                'paragraphStyle' => ['alignment' => "$alignment"],
                'fields' => 'alignment'
            ]
        ))
    ];
    $batchUpdateRequest = new Google_Service_Docs_BatchUpdateDocumentRequest(['requests' => $requests]);
    if ($docServices->documents->batchUpdate($fileId, $batchUpdateRequest)) {
        //print ("<br>DBG->Update no fileid:$fileId ! OK!<br>");
        return true;
    } else {
        //print ("<br>DBG->ERROR 0x2000 Update no fileid:$fileId ! OK!<br>");
        return false;
    }
}

function getarqid($client,$criaarquivo,$idarquivo,$title,$service):string
{
    //checa se recebeu o id do arquivo do post e se tem o criar arquivo
    //decide se cria o arquivo e pega o id ou se usa o id que recebeu
    if ((!is_null($criaarquivo)) or (!is_null($idarquivo))) { //se nao recebeu o id do arquivo do post e nao tem criar arquivo falha, erro 2001 nao sabe o arquivo a alterar! //se recebeu o id do arquivo do post e tem criar arquivo, apresenta falha, erro 3001 prossegue com o ID e nao cria novo arquivo!
        if (($idarquivo <> false) or (!is_null($idarquivo)) and ((is_null($criaarquivo)) or ($criaarquivo == false))) {
            //se recebeu o id do arquivo do post e nao tem criar arquivo trueok, certinho
            //abre o arquivo que veio o id
            //print("<br>DBG-> chegou no recebeu id aquivo!!! Parando!!<br>");
            //exit;
            return(false); //user o $idarquivo ou $fileid
        } elseif (($idarquivo == false) or (is_null($idarquivo)) and ((is_null($criaarquivo)) or ($criaarquivo == false))) {
            //se recebeu o cria arquivo e nao recebeu o id do arquivo
            //crio o arquivo
            //Create the Doc file
            //print("<br>DBG-> chegou no criaraquivo!!! Parando!!<br>");
            //exit;
            return(true);// criar aquivo novo
        } else {
            echo '<br><hr><br>Erro 0x0013 na funcao getarqid! Saindo!<br><hr><br>';
            exit;
            //return(['false']);
        }
    } else {
        echo '<br><hr><br>Erro 0x0014 na funcao getarqid! Saindo!<br><hr><br>';
        exit;
        //return(['false']);
    }
    //exit;
}

function criaarquivo ($client, $title,$service)
{
    $file = new Google_Service_Drive_DriveFile();
    $file->setDescription("$title");
    $file->setName("$title");
    $file->setMimeType('application/vnd.google-apps.document');
    if ($file = $service->files->create($file)){
        return $file;
    } else {
        return 'false'; //false;
    }

}

/* Ad hoc functions to make the examples marginally prettier.*/
/* Library of Utils Functions */
function isWebRequest(): bool
{
    return isset($_SERVER['HTTP_USER_AGENT']);
}
function pageHeader($title): string
{
    $ret = '<!doctype html>
  <html lang="br">
  <head>
    <title>' . $title . "</title>
    <link href='styles/style.css' rel='stylesheet' type='text/css' />
  </head>
  <body>\n";
    $ret .= '<header><h1>' . $title . '</h1></header>';

    // Start the session (for storing access tokens and things)
    if (!headers_sent()) {
        session_start();
    }
    return $ret;
}
function pageFooter($txtrodape): string
{
    $ret = '';
    if ($txtrodape) {
        //$ret .= "<h3>Code:</h3>";
        $ret .= "<pre class='code'>";
        $ret .= $txtrodape; //htmlspecialchars($txtrodape);
        $ret .= '</pre>';
    }
    $ret .= '</html>';
    return $ret;
}
function missingApiKeyWarning(): string
{
    $ret = "<h3 class='warn'>
      Warning: You need to set a Simple API Access key from the
      <a href='https://developers.google.com/console'>Google API console</a>
    </h3>";
    return $ret;
}
function missingClientSecretsWarning(): string
{
    $ret = "<h3 class='warn'>
      Warning: You need to set Client ID, Client Secret and Redirect URI from the
      <a href='https://developers.google.com/console'>Google API console</a>
    </h3>";
    return $ret;
}
function missingServiceAccountDetailsWarning(): string
{
    $ret = "<h3 class='warn'>
      Warning: You need download your Service Account Credentials JSON from the
      <a href='https://developers.google.com/console'>Google API console</a>.
    </h3>
    <p>
      Once downloaded, move them into the root directory of this repository and
      rename them 'service-account-credentials.json'.
    </p>
    <p>
      In your application, you should set the GOOGLE_APPLICATION_CREDENTIALS environment variable
      as the path to this file, but in the context of this example we will do this for you.
    </p>";
    return $ret;
}
function missingOAuth2CredentialsWarning($credfile): string
{
    $ret = "
    <h3 class='warn'>
      Warning: You need to set the location of your OAuth2 Client Credentials from the
      <a href='https://developers.google.com/console'>Google API console</a>.
    </h3>
    <p>
      Once downloaded, move them into the root directory of this repository and
      rename them 'oauth-credentials.json'.
    </p>
    <p>
        credfile= $credfile    
    </p>
    ";
    return $ret;
}
function invalidCsrfTokenWarning(): string
{
    $ret = "<h3 class='warn'>
      The CSRF token is invalid, your session probably expired. Please refresh the page.
    </h3>";
    return $ret;
}
function checkServiceAccountCredentialsFile(): bool
{
    // service account creds
    $application_creds = __DIR__ . '/service-account-credentials.json';
    return file_exists($application_creds) ? $application_creds : false;
}
/*function getCsrfToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
    return $_SESSION['csrf_token'];
}*/
function validateCsrfToken(): bool
{
    return isset($_REQUEST['csrf_token'])
        && isset($_SESSION['csrf_token'])
        && $_REQUEST['csrf_token'] === $_SESSION['csrf_token'];
}
function getOAuthCredentialsFile($credfile)
{
    // oauth2 creds
    $oauth_creds = __DIR__ . '/' . $credfile;
    if (file_exists($oauth_creds)) {
        return $oauth_creds;
    }
    return false;
}
function setClientCredentialsFile($apiKey)
{
    $file = __DIR__ . '/../../tests/.apiKey';
    file_put_contents($file, $apiKey);
}
function getApiKey()
{
    $file = __DIR__ . '/../../tests/.apiKey';
    if (file_exists($file)) {
        return file_get_contents($file);
    }
    return false;
}
function setApiKey($apiKey)
{
    $file = __DIR__ . '/../../tests/.apiKey';
    file_put_contents($file, $apiKey);
}
?>