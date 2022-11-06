<?php
session_start();

/**
 * CONFIGURATION
 * *********************
 */
define("CREDENTIALS", [
    'admin' => '12345678'
]);

define("ENTRY_LABEL_SINGULAR", "item");
define("ENTRY_LABEL_PLURAL", "items");

define("DB_FILE", __DIR__ . "/db.json");
define("ADMIN_PAGE_URL", $_SERVER['PHP_SELF']);

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

/**
 * Adds PHP BasicAuth security check
 */
function addBasicAuthSecurity()
{

    // Security
    $valid_users = array_keys(CREDENTIALS);

    $user = $_SERVER['PHP_AUTH_USER'] ?? null;
    $pass = $_SERVER['PHP_AUTH_PW'] ?? null;

    $validated = (in_array($user, $valid_users)) && ($pass == CREDENTIALS[$user]);

    if (!$validated) {
        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');
        die("Not authorized");
    }
}


/**
 * Add unique ID to entries if missing
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
 * Read the JSON database
 *
 * @return object
 */
function readJSON()
{
    $data = file_get_contents(DB_FILE);
    $data = json_decode($data);

    $data = normalizeData($data);

    return $data;
};

/**
 * Write the JSON database
 *
 * @return boolean
 */
function writeJSON($data)
{
    $data = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents(DB_FILE, $data);
    return true;
}

/**
 * Add a page message
 *
 * @param string $message Message body
 * @param string $type Optional. success, danger, warning, info.
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

/**
 * Print page messages and clean the messages list
 */
function getPageMessages()
{

    $pageMessage = $_SESSION['pageMessages'] ?? [];

    $output = '';
    foreach ($pageMessage as $msg) {
        $output .= "<div class='alert alert-$msg[1] alert-dismissible fade show' role='alert'>$msg[0]<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
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

addBasicAuthSecurity();



$db = readJSON(); // Get the database data

$pageAction = getPageAction();


switch ($pageAction) {
    case ACTION__CREATE:
        // Nothing to do.
        break;
    case ACTION__EDIT:
        if (!itemEdit()) {
            addPageMessage("Can't edit " . ENTRY_LABEL_SINGULAR . ". The 'id' is missing.", "danger");
            header("Location: " . ADMIN_PAGE_URL);
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
        header("Location: " . ADMIN_PAGE_URL);
        die();
        break;
    case ACTION__DELETE:
        $res = itemDelete();
        if ($res === false) {
            addPageMessage("Can't delete " . ENTRY_LABEL_SINGULAR . ". The 'id' is missing.", "danger");
        } else {
            addPageMessage(ucfirst(ENTRY_LABEL_SINGULAR) . " <strong>'$res->title'</strong> deleted.", "success");
        }
        header("Location: " . ADMIN_PAGE_URL);
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

    <style>
        #search-box {
            position: relative;
        }

        #search-box button {
            display: none;
            font-size: .6em;
            position: absolute;
            right: 1em;
            top: 50%;
            transform: translateY(-50%);
        }

        #search-box input:valid {
            padding-right: 2em;
        }

        #search-box input:valid+button {
            display: block;
        }

        #entriesTable.filtered>tbody>tr:not(.filtered-in) {
            display: none;
        }

        #entriesTable td.tags .badge {
            cursor: pointer;
        }
    </style>
</head>

<body class="container">

    <main class="row">
        <div class="wrap offset-sm-1 col-sm-10 mt-3">

            <?php echo getPageMessages(); ?>

            <h1>BackOffice Lite</h1>

            <hr>

            <?php
            if (in_array($pageAction, [ACTION__DELETE, ACTION__LIST])) {
            ?>


                <div class="mb-3 row">
                    <div class="col">
                        <a href="<?php echo ADMIN_PAGE_URL; ?>?<?php echo http_build_query(["action" => ACTION__CREATE]); ?>" class="btn btn-primary btn-sm mt-1">Add new <?php echo ENTRY_LABEL_SINGULAR; ?> <i class="bi-plus-lg "></i></a>
                    </div>
                    <div class="col-sm-6 col-lg-3 mt-2 mt-sm-0">
                        <form>
                            <label class="form-label visually-hidden ">Filter by Tags</label>
                            <div id="search-box">
                                <input class="form-control" type="text" id="filter-tags" placeholder="Filter by tags" required>
                                <button type="reset" class="btn-close" aria-label="Clear"></button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="entriesTable" class="table table-striped table-hover">

                        <thead class="table-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Title</th>
                                <th scope="col">Description</th>
                                <th scope="col">URL</th>
                                <th scope="col">Tags</th>
                                <th scope="col" width="90px">Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            foreach ($db->items as $key => $item) {
                            ?>
                                <tr data-id="<?php echo $item->id; ?>" data-tags="<?php echo join(',', $item->tags); ?>" data-title="<?php echo $item->title; ?>" data-url="<?php echo $item->url; ?>">
                                    <th class="index" scope="row"><small><?php echo $key + 1; ?></small></th>
                                    <td class="title"><?php echo $item->title; ?></td>
                                    <td class="description"><?php echo $item->description; ?></td>
                                    <td class="url"><?php if (!empty($item->url)) {
                                                        echo "<a target='_blank' href='$item->url'>$item->url</a>";
                                                    } ?></td>
                                    <td class="tags">
                                        <?php if (!empty($item->tags)) { ?>
                                            <span class="badge rounded-pill bg-secondary"><?php echo join('</span> <span class="badge rounded-pill bg-secondary">', $item->tags); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td class="options">
                                        <a class="btn btn-light btn-sm" href="<?php echo ADMIN_PAGE_URL; ?>?<?php echo http_build_query(["action" => ACTION__EDIT, "id" => $item->id]); ?>" title="Edit <?php echo ENTRY_LABEL_SINGULAR; ?>"><i class="bi-pencil"></i><span class="visually-hidden">Edit</span></a>
                                        <a class="btn btn-light btn-sm" data-confirm="Are you sure you want to delete this <?php echo ENTRY_LABEL_SINGULAR; ?>?" href="<?php echo ADMIN_PAGE_URL; ?>?<?php echo http_build_query(["action" => ACTION__DELETE, "id" => $item->id]); ?>" title="Delete <?php echo ENTRY_LABEL_SINGULAR; ?>"><i class="bi-trash"></i><span class="visually-hidden">Delete</span></a>
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>



            <?php
            } elseif (in_array($pageAction, [ACTION__EDIT, ACTION__CREATE])) {
            ?>
                <?php

                $title = "Add new " . ENTRY_LABEL_SINGULAR;
                // Default empty item for create page action
                $item = (object)[
                    "id" => null,
                    "title" => "",
                    "description" => "",
                    "url" => "",
                    "tags" => []
                ];

                if ($pageAction == ACTION__EDIT) {

                    $id = $_GET['id'];
                    $item_index = array_search($id, array_column($db->items, 'id'));
                    $item = $db->items[$item_index];
                    $title = "Edit " . ENTRY_LABEL_SINGULAR . " <small class='text-muted'>#$id</small>";
                }

                ?>

                <h2><?php echo $title; ?></h2>

                <form class="needs-validation" action="<?php echo ADMIN_PAGE_URL; ?>" method="post" class="form" novalidate>
                    <fieldset>
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input class="form-control" type="text" name="title" value="<?php echo $item->title ?>" required>
                            <div class="invalid-feedback">
                                Please add an entry title.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Desciption</label>
                            <textarea class="form-control" name="description" id="" cols="30" rows="10"><?php echo $item->description ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL</label>
                            <input class="form-control" type="url" name="url" value="<?php echo $item->url ?>" required placeholder="https://www.mysite.com">
                            <div class="invalid-feedback">
                                Please add a valid URL.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tags</label>
                            <input class="form-control" type="text" name="tags" value="<?php echo join(', ', $item->tags); ?>">
                        </div>
                    </fieldset>

                    <?php
                    if ($pageAction == ACTION__EDIT) {
                    ?>
                        <input type="hidden" name="id" value="<?php echo $item->id; ?>">

                        <button class="btn btn-primary " type="submit" name="action" value="<?php echo ACTION__UPDATE; ?>">Update <?php echo ENTRY_LABEL_SINGULAR; ?></button>
                        <a class="btn btn-danger float-end" data-confirm="Are you sure you want to delete this <?php echo ENTRY_LABEL_SINGULAR; ?>?" href="<?php echo ADMIN_PAGE_URL; ?>?<?php echo http_build_query(["action" => ACTION__DELETE, "id" => $item->id]); ?>">Delete <?php echo ENTRY_LABEL_SINGULAR; ?></a>


                    <?php } else { ?>
                        <button class="btn btn-primary" type="submit" name="action" value="<?php echo ACTION__UPDATE; ?>">Create <?php echo ENTRY_LABEL_SINGULAR; ?></button>
                    <?php } ?>

                    <a class="btn btn-light" href="<?php echo ADMIN_PAGE_URL; ?>">Cancel</a>
                </form>



                <script>
                    (() => {
                        'use strict'

                        // Fetch all the forms we want to apply custom Bootstrap validation styles to
                        const forms = document.querySelectorAll('.needs-validation')

                        // Loop over them and prevent submission
                        Array.from(forms).forEach(form => {
                            form.addEventListener('submit', event => {
                                if (!form.checkValidity()) {
                                    event.preventDefault()
                                    event.stopPropagation()
                                }

                                form.classList.add('was-validated')
                            }, false)
                        })
                    })()
                </script>

            <?php
            }
            ?>

        </div>
    </main>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.1/js/bootstrap.min.js" integrity="sha512-EKWWs1ZcA2ZY9lbLISPz8aGR2+L7JVYqBAYTq5AXgBkSjRSuQEGqWx8R1zAX16KdXPaCjOCaKE8MCpU0wcHlHA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        (() => {

            const initFiltersTag = function() {

                const table = document.querySelector('#entriesTable');

                const filter_tags_input = document.querySelector('input#filter-tags');


                const filterByTags = function() {
                    var filter_values = filter_tags_input.value.split(',');
                    filter_values = filter_values.map(v => v.trim());
                    filter_values = filter_values.filter(v => v);

                    // Clean prevous filtered-in states
                    const tr_list = document.querySelectorAll('tr.filtered-in');
                    for (const tr of tr_list) {
                        tr.classList.remove('filtered-in');
                    }

                    if (filter_values.length) {
                        table.classList.add('filtered');

                        for (const filter_value of filter_values) {
                            const tr_list = document.querySelectorAll('tr[data-tags*="' + filter_value + '"]');
                            for (const tr of tr_list) {
                                tr.classList.add('filtered-in');;
                            }
                        }
                    } else {
                        table.classList.remove('filtered');
                    }
                }
                filter_tags_input.addEventListener('keyup', event => {
                    filterByTags();
                });

                document.querySelector('#search-box button').addEventListener('click', event => {
                    setTimeout(filterByTags, 100)
                });

                const tag_bages = document.querySelectorAll('td.tags span.badge');
                for (const tag_badge of tag_bages) {

                    tag_badge.addEventListener('click', event => {
                        const el = event.target;
                        const badge_val = el.textContent;

                        var filter_values = filter_tags_input.value.split(',');
                        filter_values = filter_values.map(v => v.trim());
                        filter_values = filter_values.filter(v => v);

                        filter_values.push(badge_val);
                        filter_values = [...new Set(filter_values)]; // Remove duplicated from the set

                        filter_tags_input.value = filter_values.join(', ');

                        filterByTags(); //Update filters
                    });
                }
            }

            const initEditForm = function() {

                const confirmButtons = [...document.querySelectorAll('button[data-confirm], a[data-confirm]')];
                for (const confirmButton of confirmButtons) {
                    confirmButton.addEventListener('click', event => {

                        const message = event.currentTarget.dataset.confirm;
                        if (!confirm(message)) {
                            event.preventDefault();
                        };

                    });
                }

            }


            initFiltersTag();
            initEditForm();
        })()
    </script>

</body>

</html>
