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
    $title = "Edit ". ENTRY_LABEL_SINGULAR ." <small class='text-muted'>#$id</small>";
}

?>

<h2><?php echo $title; ?></h2>

    <form class="needs-validation" action="<?php echo $boPage; ?>" method="post" class="form" novalidate>
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
            <a class="btn btn-danger float-end" data-confirm="Are you sure you want to delete this <?php echo ENTRY_LABEL_SINGULAR;?>?" href="<?php echo $boPage; ?>?<?php echo http_build_query(["action" => ACTION__DELETE, "id" => $item->id]); ?>">Delete <?php echo ENTRY_LABEL_SINGULAR; ?></a>


        <?php } else { ?>
            <button class="btn btn-primary" type="submit" name="action" value="<?php echo ACTION__UPDATE; ?>">Create <?php echo ENTRY_LABEL_SINGULAR; ?></button>
        <?php } ?>

        <a class="btn btn-light" href="<?php echo $boPage; ?>">Cancel</a>
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
