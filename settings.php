<?php
function relatos_page_admin() {
    global $relatos_texts;

    $config = get_option('relatos_config');

    $default_filter_list = array(
                                'collection' =>  __('Collection','relatos'),
                                'thematic_area' =>  __('Thematic Area','relatos'),
                                'population_group' =>  __('Population Group','relatos'),
                                'country' =>  __('Country','relatos'),
    );

    if ($relatos_texts['filter']){
        $available_filter_list = array_merge($relatos_texts['filter'], $default_filter_list);
    }else{
        $available_filter_list   = $default_filter_list;
        $relatos_texts['filter'] = $default_filter_list;
    }

    $config_filter_list = array();
    if ( $config['available_filter'] ){
        $config_filter_list = explode(';', $config['available_filter']);
    }

    $custom_color = ( $config['custom_color'] ) ? sanitize_text_field($config['custom_color']) : '#2482A0';
?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32"></div>
        <h2><?php _e('Experience Reports record settings', 'relatos'); ?></h2>
        <form method="post" action="options.php">

            <?php settings_fields('relatos-settings-group'); ?>

            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row"><?php _e('Plugin page', 'relatos'); ?>:</th>
                        <td><input type="text" name="relatos_config[plugin_slug]" value="<?php echo ($config['plugin_slug'] != '' ? $config['plugin_slug'] : 'relatos'); ?>" class="regular-text code"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Filter query', 'relatos'); ?>:</th>
                        <td><input type="text" name="relatos_config[initial_filter]" value='<?php echo $config['initial_filter'] ?>' class="regular-text code"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('AddThis profile ID', 'relatos'); ?>:</th>
                        <td><input type="text" name="relatos_config[addthis_profile_id]" value="<?php echo $config['addthis_profile_id'] ?>" class="regular-text code"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Google Analytics code', 'relatos'); ?>:</th>
                        <td><textarea name="relatos_config[google_analytics_code]" rows="3" class="regular-text code"><?php echo $config['google_analytics_code'] ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Fulltext', 'relatos'); ?>:</th>
                        <td>
                            <label for="present_alternative_links">
                                <input type="checkbox" name="relatos_config[alternative_links]" value="true" id="present_alternative_links" <?php echo (isset($config['alternative_links']) ?  " checked='true'" : '') ;?> ></input>
                                <?php _e('Present alternative fulltext links', 'relatos'); ?>
                            </label>
                        </td>
                    </tr>
                    <?php
                        if ( function_exists( 'pll_the_languages' ) ) {
                            $available_languages = pll_languages_list();
                            $available_languages_name = pll_languages_list(array('fields' => 'name'));
                            $count = 0;

                            foreach ($available_languages as $lang) {
                                $key_name = 'plugin_title_' . $lang;
                                $home_url = 'home_url_' . $lang;

                                echo '<tr valign="top">';
                                echo '    <th scope="row"> ' . __("Home URL", "relatos") . ' (' . $available_languages_name[$count] . '):</th>';
                                echo '    <td><input type="text" name="relatos_config[' . $home_url . ']" value="' . $config[$home_url] . '" class="regular-text code"></td>';
                                echo '</tr>';

                                echo '<tr valign="top">';
                                echo '    <th scope="row"> ' . __("Page title", "relatos") . ' (' . $available_languages_name[$count] . '):</th>';
                                echo '    <td><input type="text" name="relatos_config[' . $key_name . ']" value="' . $config[$key_name] . '" class="regular-text code"></td>';
                                echo '</tr>';
                                $count++;
                            }
                        } else {
                            echo '<tr valign="top">';
                            echo '   <th scope="row">' . __("Page title", "relatos") . ':</th>';
                            echo '   <td><input type="text" name="relatos_config[plugin_title]" value="' . $config["plugin_title"] . '" class="regular-text code"></td>';
                            echo '</tr>';
                        }
                    ?>
                    <tr valign="top">
                        <th scope="row"><?php _e('Related Documents filter', 'relatos'); ?>:</th>
                        <td>
                            <input type="text" name="relatos_config[default_filter_db]" value='<?php echo $config['default_filter_db']; ?>' class="regular-text code">
                            <small style="display: block;">* <?php _e('The filters must be separated by commas.', 'relatos'); ?></small>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('More Related filter', 'relatos'); ?>:</th>
                        <td>
                            <input type="text" name="relatos_config[extra_filter_db]" value='<?php echo $config['extra_filter_db']; ?>' class="regular-text code">
                            <small style="display: block;">* <?php _e('The filters must be separated by commas.', 'relatos'); ?></small>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Search filters', 'relatos');?>:</th>
                        <td>
                            <table border=0>
                                <tr>
                                    <td>
                                        <p align="left"><?php _e('Available', 'relatos');?><br>
                                            <ul id="sortable1" class="connectedSortable">
                                                <?php
                                                    foreach ($available_filter_list as $filter_field => $filter_title){
                                                        if ( !in_array($filter_field, $config_filter_list) ) {
                                                            echo '<li class="ui-state-default" id="' .  $filter_field .'">' . $filter_title . '</li>';
                                                        }
                                                    }
                                                ?>
                                            </ul>
                                        </p>
                                    </td>
                                    <td>
                                        <p align="left"><?php _e('Selected', 'relatos');?> <br>
                                          <ul id="sortable2" class="connectedSortable">
                                              <?php
                                                foreach ($config_filter_list as $selected_filter) {
                                                    $filter_title = $available_filter_list[$selected_filter];
                                                    if ($filter_title != ''){
                                                        echo '<li class="ui-state-default" id="' . $selected_filter . '">' . $filter_title . '</li>';
                                                    }
                                                }
                                              ?>
                                          </ul>
                                          <input type="hidden" id="available_filter_aux" name="relatos_config[available_filter]" value="<?php echo $config['available_filter']; ?>" >
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="custom-color"><?php _e('Custom Color', 'relatos'); ?></label>
                        </th>
                        <td>
                            <input type="color" id="custom-color" name="relatos_config[custom_color]" value="<?php echo ( $config['custom_color'] ) ? $config['custom_color'] : '#2482A0'; ?>" class="regular-text input-custom-color" data-color="#2482A0" style="width: 235px;">
                            <p class="description hex-color" style="display: inline; text-transform: uppercase;"><?php echo ( $config['custom_color'] ) ? $config['custom_color'] : '#2482A0'; ?></p>
                            <!-- <div class="custom-color" style="height: 30px; width: 30px; float: left; margin-right: 8px; background: <?php echo $custom_color; ?>; "></div> -->
                            <!-- <p class="description"><?php _e('Example', 'relatos'); ?>: #2482A0</p> -->
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save changes') ?>" />
            </p>
        </form>
    </div>
    <script type="text/javascript">
        var $j = jQuery.noConflict();

        $j( function() {
            $j("#sortable1, #sortable2").sortable({
                connectWith: ".connectedSortable"
            });

            $j("#sortable2").sortable({
                update: function(event, ui) {
                    var changedList = this.id;
                    var selected_filter = $j(this).sortable('toArray');
                    var selected_filter_list = selected_filter.join(';');
                    if (!selected_filter_list) selected_filter_list = false;
                    $j('#available_filter_aux').val(selected_filter_list);
                }
            });

            $j('.input-custom-color').on( "change", function(){
                var bgcolor = $j(this).val();
                if ( bgcolor ) {
                    $j(this).next().text(bgcolor);
                } else {
                    bgcolor = $j(this).data('color');
                    $j(this).next().text(bgcolor);
                }
            });
/*
            $j('.input-custom-color').on( "blur", function(){
                var bgcolor = $j(this).val();
                if ( bgcolor ) {
                    $j(this).next().css('background-color', bgcolor);
                } else {
                    bgcolor = $j(this).data('color');
                    $j(this).next().css('background-color', bgcolor);
                }
            });
*/
        });
    </script>

    <?php
}
?>
