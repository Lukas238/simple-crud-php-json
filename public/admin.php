<?php
session_start();

// Security
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="BackOffice"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Text to send if user hits Cancel button';
    exit;
}

/**
 * CONFIGURATION
 * *********************
 */

define("JSON_FILE", __DIR__ ."/inc/db.json");


define("ENTRY_LABEL_SINGULAR", "item");
define("ENTRY_LABEL_PLURAL", "items");

// Page actions constants
define("ACTION__LIST", 0);
define("ACTION__CREATE", 1);
define("ACTION__EDIT", 2);
define("ACTION__UPDATE", 3);
define("ACTION__DELETE", 4);

/**
 * FUNCTIONS
 * *********************
 */

function normalizeData($data = null)
{
    foreach ($data->items as $key => $item) {
        if (empty($item->id)) {
            $item->id = uniqid();
        }
    }
    writeJSON($data);

    return $data;
}

/**
 * Read the JSON data base
 *
 * @return object
 */
function readJSON()
{
    $data = file_get_contents(JSON_FILE);
    $data = json_decode($data);

    $data = normalizeData($data);

    return $data;
};

function writeJSON($data)
{
    $data = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents(JSON_FILE, $data);
    return true;
}

/**
 * Add a page message
 *
 * @param string $message Message body
 * @param string $type Values: success, danger, warning, info. Default 'null'.
 *
 * @return null
 */
function addPageMessage($message, $type = null)
{
    $_SESSION['pageMessages'][] = [
        $message,
        $type
    ];
}

function getPageMessages()
{

    $pageMessage = $_SESSION['pageMessages'] ?? [];

    $output = '';
    foreach ($pageMessage as $msg) {
        $output .= "
            <div class='alert alert-$msg[1] alert-dismissible fade show' role='alert'>
                $msg[0]
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
        ";
    }

    $_SESSION['pageMessages'] = [];
    return $output;
}

/**
 * Identify the page action
 */
function getPageAction()
{
    $output = ACTION__LIST; // Default

    $action = $_SERVER['REQUEST_METHOD'] === 'POST' ? @$_POST['action'] : @$_GET['action'];

    $action = !empty($action) ? $action : null;

    if ($action) {
        if (in_array($action, [ACTION__EDIT, ACTION__LIST, ACTION__CREATE, ACTION__DELETE, ACTION__UPDATE])) {
            $output = $action;
        };
    }

    return $output;
}

function itemEdit()
{
    $id = @$_GET['id'];

    if (empty($id)) {
        return false;
    };

    return true;
}

function itemUpdate()
{
    global $db;

    $item = (object)[
        "id" => @$_POST['id'],
        "title" => trim(@$_POST['title']),
        "description" => trim(@$_POST['description']),
        "url" => trim(@$_POST['url']),
        "tags" => empty(@$_POST['tags']) ? [] : array_map("trim", explode(',', @$_POST['tags']))
    ];

    // Validate items
    if (empty($item->id) && !is_null($item->id)) { // Allow null ID as this means this is a new entry
        return false;
    } elseif (empty($item->title)) {
        return false;
    } elseif (empty($item->url)) {
        return false;
    }

    $item->tags =  array_map('strtolower', $item->tags); // Normalize case to lowercase
    $item->tags = array_unique($item->tags); // Remove duplicated

    if (is_null($item->id)) { // Create new entry
        $item->id = uniqid();
        $db->items[] = $item;
    } else { // Update existing entry
        $item_index = array_search($item->id, array_column($db->items, 'id'));
        $db->items[$item_index] = $item;
    }

    $res = writeJSON($db);

    if ($res === false) {
        return false;
    }

    return $item;
}

function itemDelete()
{
    global $db;

    $id = @$_GET['id'];

    if (empty($id)) {
        return false;
    };

    // Actualizar la DB

    $item_index = array_search($id, array_column($db->items, 'id'));

    if ($item_index === false) {
        return false;
    }
    $item = $db->items[$item_index];

    unset($db->items[$item_index]); // Delete the key position
    $db->items = array_values($db->items); // Reorder the array. This prevent json_encode to add keys to the items array.

    $res = writeJSON($db);

    if ($res === false) {
        return false;
    }

    return $item;
}

/**
 * SCRIPT
 * *********************
 */

$boPage = $_SERVER['PHP_SELF']; // BackOffice page name

$db = readJSON(); // Get the database data

$pageAction = getPageAction();


switch ($pageAction) {
    case ACTION__CREATE:
        // Nothing to do.
        break;
    case ACTION__EDIT:
        if (!itemEdit()) {
            addPageMessage("Can't edit " . ENTRY_LABEL_SINGULAR . ". The 'id' is missing.", "danger");
            header("Location: $boPage");
            die();
        }
        break;
    case ACTION__UPDATE:
        $res = itemUpdate();
        if ($res === false) {
            addPageMessage("Can't update " . ENTRY_LABEL_SINGULAR . ". One or more values are missing.", "danger");
        } else {
            addPageMessage(ucfirst(ENTRY_LABEL_SINGULAR) . " <strong>'$res->title'</strong> updated.", "success");
        }
        header("Location: $boPage");
        die();
        break;
    case ACTION__DELETE:
        $res = itemDelete();
        if ($res === false) {
            addPageMessage("Can't delete " . ENTRY_LABEL_SINGULAR . ". The 'id' is missing.", "danger");
        } else {
            addPageMessage(ucfirst(ENTRY_LABEL_SINGULAR) . " <strong>'$res->title'</strong> deleted.", "success");
        }
        header("Location: $boPage");
        die();
        break;
    default:
        // ACTION__LIST;
};


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BackOffice</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.1/css/bootstrap.min.css" integrity="sha512-Ez0cGzNzHR1tYAv56860NLspgUGuQw16GiOOp/I2LuTmpSK9xDXlgJz3XN4cnpXWDmkNBKXR/VDMTCnAaEooxA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
</head>

<body>

    <main class="row">
        <div class="wrap offset-sm-1 col-sm-10">

            <?php echo getPageMessages(); ?>

            <h1>BackOffice Lite</h1>

            <hr>

            <?php
            switch ($pageAction) {
                case ACTION__DELETE:
                case ACTION__LIST:
                    include 'inc/tmpl_list.php';
                    break;
                case ACTION__EDIT:
                case ACTION__CREATE:
                    include 'inc/tmpl_edit.php';
                    break;
            }
            ?>

        </div>
    </main>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.1/js/bootstrap.min.js" integrity="sha512-EKWWs1ZcA2ZY9lbLISPz8aGR2+L7JVYqBAYTq5AXgBkSjRSuQEGqWx8R1zAX16KdXPaCjOCaKE8MCpU0wcHlHA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        (() => {
        const confirmButtons = [...document.querySelectorAll('button[data-confirm], a[data-confirm]')];
        for (const confirmButton of confirmButtons) {
            confirmButton.addEventListener('click', event => {

                const message = event.currentTarget.dataset.confirm;
                if(!confirm(message)){
                    event.preventDefault();
                };

            });
        }
    })()
    </script>

</body>

</html>
