<style>
    #entriesTable.filtered>tbody>tr:not(.filtered-in) {
        display: none;
    }
    #entriesTable td.tags .badge{
        cursor: pointer;
    }
</style>

<div class="mb-3 row">
    <div class="col">
        <a href="<?php echo $boPage; ?>?<?php echo http_build_query(["action" => ACTION__CREATE]); ?>" class="btn btn-primary btn-sm mt-1">Add new <?php echo ENTRY_LABEL_SINGULAR; ?> <i class="bi-plus-lg "></i></a>
    </div>
    <div class="col-sm-6 col-lg-3 mt-2 mt-sm-0">
        <label class="form-label col-form-label-sm">Filter by Tags</label><input class="form-control" type="text" id="filter-tags" placeholder="Filter by tags. Ex.: tag1, tag2">
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
                        <a class="btn btn-light btn-sm" href="<?php echo $boPage; ?>?<?php echo http_build_query(["action" => ACTION__EDIT, "id" => $item->id]); ?>" title="Edit <?php echo ENTRY_LABEL_SINGULAR;?>"><i class="bi-pencil"></i><span class="visually-hidden">Edit</span></a>
                        <a class="btn btn-light btn-sm" data-confirm="Are you sure you want to delete this <?php echo ENTRY_LABEL_SINGULAR;?>?" href="<?php echo $boPage; ?>?<?php echo http_build_query(["action" => ACTION__DELETE, "id" => $item->id]); ?>" title="Delete <?php echo ENTRY_LABEL_SINGULAR;?>"><i class="bi-trash"></i><span class="visually-hidden">Delete</span></a>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
</div>

<script>
    (() => {

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

    })()
</script>
