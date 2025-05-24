<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

class DocsManager {
    private $client;
    private $docs;
    private $drive;

    public function __construct(string $credentialsPath, string $appName = 'GDocs PHP v2') {
        $this->client = new Google\Client();
        $this->client->setApplicationName($appName);
        $this->client->setScopes([
            Google_Service_Docs::DOCUMENTS,
            Google_Service_Drive::DRIVE,
            Google_Service_Sheets::SPREADSHEETS,
        ]);
        $this->client->setAuthConfig($credentialsPath);
        $redirectUri = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        $this->client->setRedirectUri($redirectUri);
        $this->client->setAccessType('online');

        if (!headers_sent()) {
            session_start();
        }
        if (isset($_GET['logout'])) {
            unset($_SESSION['token']);
        }
        if (isset($_GET['code'])) {
            $token = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
            $this->client->setAccessToken($token);
            $_SESSION['token'] = $token;
            header('Location: ' . filter_var($redirectUri, FILTER_SANITIZE_URL));
            exit;
        }
        if (!empty($_SESSION['token'])) {
            $this->client->setAccessToken($_SESSION['token']);
            if ($this->client->isAccessTokenExpired()) {
                unset($_SESSION['token']);
            }
        }
        if (!$this->client->getAccessToken()) {
            $authUrl = $this->client->createAuthUrl();
            echo "<p><a href='" . htmlspecialchars($authUrl, ENT_QUOTES) . "'>Authenticate with Google</a></p>";
            exit;
        }

        $this->docs = new Google_Service_Docs($this->client);
        $this->drive = new Google_Service_Drive($this->client);
    }

    public function createDocument(string $title): string {
        $file = new Google_Service_Drive_DriveFile([
            'name' => $title,
            'mimeType' => 'application/vnd.google-apps.document',
        ]);
        $created = $this->drive->files->create($file);
        return $created->id;
    }

    public function insertText(string $documentId, string $text, array $opts = []): void {
        $end = strlen($text) + 1;
        $reqs = [];
        $reqs[] = new Google_Service_Docs_Request([
            'insertText' => [
                'text' => $text,
                'location' => ['index' => 1],
            ],
        ]);
        $style = [];
        if (!empty($opts['bold'])) $style['bold'] = true;
        if (!empty($opts['italic'])) $style['italic'] = true;
        if (!empty($opts['underline'])) $style['underline'] = true;
        if (!empty($opts['fontSize'])) {
            $style['fontSize'] = ['unit' => 'PT', 'magnitude' => (int)$opts['fontSize']];
        }
        if (!empty($opts['fontFamily'])) {
            $style['weightedFontFamily'] = ['fontFamily' => $opts['fontFamily']];
        }
        if ($style) {
            $reqs[] = new Google_Service_Docs_Request([
                'updateTextStyle' => [
                    'textStyle' => $style,
                    'range' => ['startIndex' => 1, 'endIndex' => $end],
                    'fields' => '*',
                ],
            ]);
        }
        $body = new Google_Service_Docs_BatchUpdateDocumentRequest(['requests' => $reqs]);
        $this->docs->documents->batchUpdate($documentId, $body);
    }

    public function listDocuments(int $pageSize = 10): array {
        $res = $this->drive->files->listFiles([
            'pageSize' => $pageSize,
            'fields' => 'files(id, name)',
            'q' => "mimeType='application/vnd.google-apps.document'"
        ]);
        return $res->getFiles();
    }
}

$credFile = dirname(__DIR__) . '/auth-credentials.json';

$action = $_POST['action'] ?? '';

if ($action) {
    $manager = new DocsManager($credFile);
    $docId = $_POST['documentId'] ?? '';
    $title = $_POST['title'] ?? 'New Document';
    if (!$docId) {
        $docId = $manager->createDocument($title);
    }
    $text = $_POST['text'] ?? '';
    $opts = [
        'bold' => !empty($_POST['bold']),
        'italic' => !empty($_POST['italic']),
        'underline' => !empty($_POST['underline']),
        'fontSize' => $_POST['fontSize'] ?? null,
        'fontFamily' => $_POST['fontFamily'] ?? null,
    ];
    $manager->insertText($docId, $text, $opts);
    echo "<p>Document updated: <a target='_blank' href='https://docs.google.com/document/d/" . htmlspecialchars($docId) . "/edit'>Open Document</a></p>";
    echo "<p><a href='?logout'>Logout</a></p>";
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <title>GDocs API Sample v2</title>
</head>
<body>
<form method="POST">
    <input type="hidden" name="action" value="insert">
    <p><label>Document ID (leave blank to create new): <input type="text" name="documentId"></label></p>
    <p><label>Title for new document: <input type="text" name="title" value="Sample Document"></label></p>
    <p><label>Font Size: <input type="number" name="fontSize" value="12"></label></p>
    <p><label>Font Family: <input type="text" name="fontFamily" value="Arial"></label></p>
    <p><label><input type="checkbox" name="bold"> Bold</label></p>
    <p><label><input type="checkbox" name="italic"> Italic</label></p>
    <p><label><input type="checkbox" name="underline"> Underline</label></p>
    <p><label>Text:<br><textarea name="text" rows="4" cols="60"></textarea></label></p>
    <p><button type="submit">Send</button></p>
</form>
</body>
</html>
