<?php
/*
Template Name: Experience Reports Detail
*/

global $relatos_service_url, $relatos_plugin_slug, $similar_docs_url, $solr_service_url;

$relatos_config         = get_option('relatos_config');
$relatos_initial_filter = $relatos_config['initial_filter'];
$relatos_addthis_id     = $relatos_config['addthis_profile_id'];
$relatos_about          = $relatos_config['about'];
$relatos_tutorials      = $relatos_config['tutorials'];
$alternative_links = (bool)$relatos_config['alternative_links'];

$referer = wp_get_referer();
$path = parse_url($referer);
if ( array_key_exists( 'query', $path ) ) {
    $path = parse_str($path['query'], $output);
    // echo "<pre>"; print_r($output); echo "</pre>";
    if ( array_key_exists( 'q', $output ) && !empty( $output['q'] ) ) {
        $query = $output['q'];
        $q = ( strlen($output['q']) > 10 ? substr($output['q'],0,10) . '...' : $output['q'] );
        $ref = ' / <a href="'. $referer . '">' . $q . '</a>';
    }
}

$filter = '';
$user_filter = stripslashes($output['filter']);
if ($relatos_initial_filter != ''){
    if ($user_filter != ''){
        $filter = $relatos_initial_filter . ' AND ' . $user_filter;
    }else{
        $filter = $relatos_initial_filter;
    }
}else{
    $filter = $user_filter;
}

$request_uri   = $_SERVER["REQUEST_URI"];
$request_parts = explode('/', $request_uri);
$resource_id   = sanitize_text_field($_GET['id']);

$site_language = strtolower(get_bloginfo('language'));
$lang = substr($site_language,0,2);
$locale = array(
    'pt' => 'pt_BR',
    'es' => 'es_ES',
    'fr' => 'fr_FR',
    'en' => 'en'
);

$language = array(
    'pt_BR' => __('Portuguese','relatos'),
    'es_ES' => __('Spanish','relatos'),
    'fr_FR' => __('French','relatos'),
    'en'    => __('English','relatos')
);

// status options
$status = array(
    "A" => __("Under implementation",'relatos'),
    "B" => __("Implemented and in regular operation",'relatos'),
    "C" => __("Interrupted",'relatos'),
    "D" => __("Completed",'relatos')
);

// $relatos_service_request = $solr_service_url . '/solr/relatos-experiencia/select/?q=id:' . $resource_id . '&wt=json';

$relatos_service_request = $relatos_service_url . '/api/experience/' . $resource_id . '?lang=' . $locale[$lang];

// echo "<pre>"; print_r($relatos_service_request); echo "</pre>"; die();

$response = @file_get_contents($relatos_service_request);

if ($response){
    $response_json = json_decode($response);
    $resource = $response_json[0]->main_submission;
    // $resource = $response_json->response->docs[0];

    // echo "<pre>"; print_r($response_json); echo "</pre>"; die();

    // create param to find similars
    $similar_text = $resource->title;
    if (isset($resource->mj)){
        $similar_text .= ' ' . implode(' ', $resource->mj);
    }

    $similar_docs_url = $similar_docs_url . '?adhocSimilarDocs=' . urlencode($similar_text);
    $similar_docs_request = ( $relatos_config['default_filter_db'] ) ? $similar_docs_url . '&sources=' . $relatos_config['default_filter_db'] : $similar_docs_url;
    $similar_query = urlencode($similar_docs_request);
    $related_query = urlencode($similar_docs_url);

    // create param to find publication language
    if (isset($resource->publication_language[0])){
        $publication_language = explode('|', $resource->publication_language[0]);
        $publication_language = get_publication_language($publication_language, $lang);
    }
}

$feed_url = real_site_url($relatos_plugin_slug) . 'relatos-feed?q=' . urlencode($query) . '&filter=' . urlencode($filter);

$home_url = ( $relatos_config['home_url_' . $lang] ) ? $relatos_config['home_url_' . $lang] : real_site_url();
$plugin_breadcrumb = isset($relatos_config['plugin_title_' . $lang]) ? $relatos_config['plugin_title_' . $lang] : $relatos_config['plugin_title'];
if ( empty($plugin_breadcrumb) ) $plugin_breadcrumb = get_bloginfo('name');

?>

<?php get_header('relatos');?>

<section id="sectionSearch" class="padding2 color1p relatos-search">
    <div class="container">
        <div class="col-md-12">
            <form role="search" method="get" name="formHome" id="searchForm" action="<?php echo real_site_url($relatos_plugin_slug); ?>">
                <div class="row g-3">
                    <div class="col-9 offset-1 text-right">
                        <input type="hidden" name="lang" id="lang" value="<?php echo $lang; ?>">
                        <input type="hidden" name="sort" id="sort" value="">
                        <input type="hidden" name="format" id="format" value="summary">
                        <input type="hidden" name="count" id="count" value="10">
                        <input type="hidden" name="page" id="page" value="1">
                        <input value='' name="q" class="form-control input-search" id="fieldSearch" type="text" autocomplete="off" placeholder="<?php _e('Enter one or more words', 'relatos'); ?>">
                        <a id="speakBtn" href="#"><i class="fas fa-microphone-alt"></i></a>
                    </div>
                    <div class="col-1 float-end">
                        <button type="submit" id="submitHome" class="btn btn-warning">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="padding1 color1p relatos-section">
    <div class="container viewBt">
        <nav aria-label="breadcrumb" class="relatos-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $home_url ?>"><?php _e('Home','relatos'); ?></a></li>
                <li class="breadcrumb-item"><a href="<?php echo real_site_url($relatos_plugin_slug); ?>"><?php echo $plugin_breadcrumb; ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo ( strlen($resource->title) > 90 ) ? substr($resource->title,0,90) . '...' : $resource->title; ?></li>
            </ol>
        </nav>

        <?php if ( $resource ) : ?>
            <?php if ( $relatos_config['custom_banner'] ) : ?>
            <div id="header-title" style="background: url(<?php echo $relatos_config['custom_banner']; ?>) no-repeat center top;">
            <?php else : ?>
            <div id="header-title">
            <?php endif; ?>
                <div class="row">
                    <div id="title-relatos">
                        <?php _e('Experience Reports','relatos'); ?>
                    </div>
                    <div class="offset-lg-5 col-lg-7 align-items-center">
                        <h1 class="title-teste"><?php echo $resource->title; ?></h1>  
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php if ( !$resource ) : ?>
                <div class="col-md-12 text-center">
                    <div class="alert alert-secondary" role="alert">
                        <?php echo mb_strtoupper(__('Document not found','relatos')); ?>
                    </div>
                </div>
            <?php else : ?>
                <div class="col-md-12">
                    <div class="bpBtAction">
                        <!-- AddThis Button BEGIN -->
                        <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
                            <a class="addthis_button_facebook"></a>
                            <a class="addthis_button_whatsapp"></a>
                            <a class="addthis_button_twitter"></a>
                            <a class="addthis_button_linkedin"></a>
                            <a class="addthis_button_email"></a>
                            <a class="addthis_button_print"></a>
                            <a class="addthis_button_compact"></a>
                        </div>
                        <script type="text/javascript">var addthis_config = {"data_track_addressbar":false};</script>
                        <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=<?php echo $relatos_addthis_id; ?>"></script>
                        <!-- AddThis Button END -->
                    </div>

                    <div class="relatos-data">
                        <!-- <h3><b><?php echo __('Experience Details', 'relatos'); ?></b></h3><br /> -->
                        <?php if ( $resource->description ): ?>
                            <div class="session1">
                                <h5 class="title2"><b><?php echo __('Issue', 'relatos') . '/' . __('Situation', 'relatos') . ':'; ?></b></h5>
                                <p><?php echo nl2br($resource->description); ?></p>
                                <hr />
                            </div>
                        <?php endif; ?>

                        <?php if ( $resource->full_text ): ?>
                            <div class="session1 d-none">
                                <h5 class="title2">
                                    <b><?php echo __('Fulltext', 'relatos') . ':'; ?></b>
                                    <!--a href="#fulltext" data-toggle="collapse" class="btn btn-sm btn-outline-primary">Exibir/Ocultar</a-->
                                    <div style="display:inline-block; margin-left: 10px;">
                                        <span class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="customSwitch1" href="#fulltext" data-toggle="collapse">
                                            <label class="custom-control-label" for="customSwitch1" style="color: #666; text-transform: capitalize;"><?php echo __('Show', 'relatos'); ?></label>
                                        </span>
                                    </div>
                                </h5>
                                <div class="collapse" id="fulltext">
                                    <p><?php echo nl2br($resource->full_text); ?></p>
                                </div>
                                <hr />
                            </div>
                        <?php endif; ?>

                        <?php if ( $resource->objectives ): ?>
                            <div class="session1">
                                <h5 class="title2"><b><?php echo __('Objectives', 'relatos') . '/' . __('Expected results', 'relatos') . ':'; ?></b></h5>
                                <p><?php echo nl2br($resource->objectives); ?></p>
                                <hr />
                            </div>
                        <?php endif; ?>
                        <?php if ( $resource->resources ): ?>
                            <div class="session1">
                                <h5 class="title2"><b><?php echo __('Resources', 'relatos') . ':'; ?></b></h5>
                                <p><?php echo nl2br($resource->resources); ?></p>
                                <hr />
                            </div>
                        <?php endif; ?>
                        <?php if ( $resource->main_results ): ?>
                            <div class="session1">
                                <h5 class="title2"><b><?php echo __('Main results', 'relatos') . '/' . __('Impacts', 'relatos') . ':'; ?></b></h5>
                                <p><?php echo nl2br($resource->main_results); ?></p>
                                <hr />
                            </div>
                        <?php endif; ?>
                        <?php if ( $resource->challenges_information ): ?>
                            <div class="session1">
                                <h5 class="title2"><b><?php echo __('Challenges', 'relatos') . ':'; ?></b></h5>
                                <p><?php echo nl2br($resource->challenges_information); ?></p>
                                <hr />
                            </div>
                        <?php endif; ?>
                        <?php if ( $resource->lessons_learned ): ?>
                            <div class="session1">
                                <h5 class="title2"><b><?php echo __('Lessons learned', 'relatos') . ':'; ?></b></h5>
                                <p><?php echo nl2br($resource->lessons_learned); ?></p>
                                <hr />
                            </div>
                        <?php endif; ?>

                        <?php $relatos_docs = get_relatos_attachment($response_json[0], 'document'); ?>
                        <?php if ( $resource->full_text || $relatos_docs  ) : ?>
                            <div class="session1">
                                <?php echo __('Access the experience in fulltext:', 'relatos'); ?>
                                <div class="btn-group" role="group" aria-label="Basic outlined example">
                                    <?php if ( $resource->full_text ) : ?>
                                        <a type="button" href="#!" class="btn btn-outline-primary" data-target="#fulltext" data-toggle="collapse"><i class="fas fa-align-justify"></i> <?php echo __('Show Fulltext', 'relatos'); ?></a>
                                    <?php endif; ?>
                                    <?php if ( $relatos_docs ) : $dcount = 0; ?>
                                        <?php foreach ($relatos_docs as $uri) : $dcount++; ?>
                                            <?php $_uri = strpos($uri, 'http') === 0 ? $uri : "https://".$uri; ?>
                                            <?php if (filter_var($_uri, FILTER_VALIDATE_URL) !== false) : ?>
                                                <?php if ( count($relatos_docs) > 1 ) : ?>
                                                    <a type="button" href="<?php echo $_uri; ?>" class="btn btn-outline-primary" target="_blank" download><i class="fas fa-file-alt"></i> <?php echo __('Download Fulltext', 'relatos') . ' ' . $dcount; ?></a>
                                                <?php else : ?>
                                                    <a type="button" href="<?php echo $_uri; ?>" class="btn btn-outline-primary" target="_blank" download><i class="fas fa-file-alt"></i> <?php echo __('Download Fulltext', 'relatos'); ?></a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php $file = $relatos_service_url . '/public/submission/' . $resource->id . '/pdf'; ?>
                                    <?php if ( $handle = @fopen($file, 'r') ) : ?>
                                        <a type="button" href="<?php echo $file; ?>" class="btn btn-outline-primary" target="_blank" download><i class="fas fa-download"></i> <?php echo __('Download Experience', 'relatos'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ( $resource->full_text ) : ?>
                                <div class="session1">
                                    <div id="fulltext" class="collapse">
                                    <hr />
                                    <h5 class="title2"><?php echo __('Fulltext', 'relatos'); ?></h5>
                                    <p><?php echo nl2br($resource->full_text); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php $relatos_videos = get_relatos_attachment($response_json[0], 'video'); ?>
<?php $relatos_images = get_relatos_attachment($response_json[0], 'image'); ?>
<?php $other_videos = get_media_embedded_in_content($resource->other_videos); ?>
<?php if ( $relatos_videos || $other_videos || $relatos_images ) : ?>
    <section class="padding1 color1p">
        <div class="container">
            <?php if ( $relatos_videos ) : ?>
                <div class="session1">
                    <h5 class="title2"><?php echo __('Videos', 'relatos'); ?></h5>
                    <div class="session1">
                        <div class="row">
                            <?php foreach ($relatos_videos as $uri): ?>
                                <?php $_uri = strpos($uri, 'http') === 0 ? $uri : "https://".$uri; ?>
                                <?php if (filter_var($_uri, FILTER_VALIDATE_URL) !== false) : ?>
                                    <div class="col-12 col-md">
                                        <div class="embed-responsive embed-responsive-21by9">
                                            <video src="<?php echo $_uri; ?>" controls="controls">
                                                <?php echo __('Your browser does not support the video tag.', 'relatos'); ?>
                                            </video>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <hr />
                    </div>
                </div>
            <?php elseif ( $resource->other_videos ): ?>
                <?php if ( $other_videos ) : ?>
                    <div class="session1">
                        <h5 class="title2"><?php echo __('Videos', 'relatos'); ?></h5>
                        <div class="embed-responsive embed-responsive-21by9">
                            <?php echo $resource->other_videos; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ( $relatos_images ) : ?>
                <div class="session1">
                    <h5 class="title2"><?php echo __('Images', 'relatos'); ?></h5>
                    <?php foreach ($relatos_images as $img): ?>
                        <div class="relatos-thumb">
                            <a href="<?php echo $img; ?>" data-lightbox="relatos-img">
                                <img src="<?php echo $img; ?>" alt="" class="img-fluid" />
                                <?php // $img_name = explode('_', basename($img)); ?>
                                <?php // echo $img_name[1]; ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <div class="clearfix"></div>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<section class="padding1 color1p">
    <div class="container">
        <h3><b><?php _e('Team','relatos'); ?></b></h3>
        <br />
        <?php if ( $resource->responsible ): ?>
            <div class="session1">
                <h5 class="title2"><b><?php echo __('Responsible', 'relatos') . ':'; ?></b></h5>
                <div class="card-grid">
                    <?php foreach ($resource->responsible as $responsible) : ?>
                        <div class="card box2 card-box card-resp">
                            <div class="card-body position-relative">
                                <?php $responsible_image = get_responsible_image($response_json[0], $responsible->filename); ?>
                                <?php if ( $responsible_image ) : ?>
                                    <a href="<?php echo $responsible_image[0]; ?>" data-lightbox="resp-img-<?php echo uniqid(); ?>">
                                        <div class="responsible-avatar">
                                            <img src="<?php echo $responsible_image[0]; ?>" alt="">
                                        </div>
                                    </a>
                                <?php endif; ?>
                                <h5 class="card-title">
                                    <?php if ( $responsible->filiation || $responsible->job || $responsible->email || $responsible->phone || $responsible->curriculum || $responsible->orcid ) : ?>
                                        <?php echo $responsible->name; ?> <small><a href="#resp" data-toggle="collapse"><i class="fa-solid fa-circle-plus"></i></a></small>
                                    <?php else : ?>
                                        <?php echo $responsible->name; ?>
                                    <?php endif; ?>
                                </h5>
                                <div class="collapse" id="resp">
                                    <p class="card-text">
                                        <?php if ( $responsible->filiation ) : ?>
                                            <b><?php echo __('Filiation', 'relatos'); ?></b><br />
                                            <?php echo $responsible->filiation; ?><br />
                                        <?php endif; ?>
                                        <?php if ( $responsible->job ) : ?>
                                            <b><?php echo __('Job', 'relatos'); ?></b><br />
                                            <?php echo $responsible->job; ?><br />
                                        <?php endif; ?>
                                        <?php if ( $responsible->email ) : ?>
                                            <b><?php echo __('Email', 'relatos'); ?></b><br />
                                            <?php echo $responsible->email; ?><br />
                                        <?php endif; ?>
                                        <?php if ( $responsible->phone ) : ?>
                                            <b><?php echo __('Phone', 'relatos'); ?></b><br />
                                            <?php echo $responsible->phone; ?><br />
                                        <?php endif; ?>
                                        <?php if ( $responsible->curriculum ) : ?>
                                            <a href="<?php echo $responsible->curriculum; ?>" class="card-link" target="_blank">
                                                <i class="far fa-file" aria-hidden="true"> </i>
                                                <?php echo __('Curriculum', 'relatos'); ?>
                                            </a>
                                            <br />
                                        <?php endif; ?>
                                        <?php if ( $responsible->orcid ) : ?>
                                            <a href="https://orcid.org/<?php echo $responsible->orcid; ?>" class="card-link" target="_blank">
                                                <i class="fa fa-external-link-alt" aria-hidden="true"> </i>
                                                <?php echo __('ORCID', 'relatos'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr />
            </div>
        <?php endif; ?>

        <?php if ( $resource->members ): $count = 0; ?>
            <div class="session1">
                <h5 class="title2"><b><?php echo __('Members', 'relatos') . ':'; ?></b></h5>
                <div class="card-grid-2">
                    <?php foreach ($resource->members as $member) : $count++; ?>
                        <div class="card card-box box3 card-member">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <?php if ( $member->filiation || $member->job || $member->academic_formation || $member->email || $member->curriculum ) : ?>
                                        <?php echo $member->name; ?> <a href="#div_<?php echo $count; ?>" data-toggle="collapse"><i class="fa-solid fa-circle-plus"></i></a>
                                    <?php else : ?>
                                        <?php echo $member->name; ?>
                                    <?php endif; ?>
                                </h6>
                                <div class="collapse" id="div_<?php echo $count; ?>">
                                    <p class="card-text">
                                        <?php if ( $member->filiation ) : ?>
                                            <b><?php echo __('Filiation', 'relatos'); ?></b><br />
                                            <?php echo $member->filiation; ?><br />
                                        <?php endif; ?>
                                        <?php if ( $member->job ) : ?>
                                            <b><?php echo __('Job', 'relatos'); ?></b><br />
                                            <?php echo $member->job; ?><br />
                                        <?php endif; ?>
                                        <?php if ( $member->academic_formation ) : ?>
                                            <b><?php echo __('Academic formation', 'relatos'); ?></b><br />
                                            <?php echo $member->academic_formation; ?><br />
                                        <?php endif; ?>
                                        <?php if ( $member->email ) : ?>
                                            <b><?php echo __('Email', 'relatos'); ?></b><br />
                                            <?php echo $member->email; ?><br />
                                        <?php endif; ?>
                                        <?php if ( $member->curriculum ) : ?>
                                            <a href="<?php echo $member->curriculum; ?>" class="card-link" target="_blank">
                                                <i class="far fa-file" aria-hidden="true"> </i>
                                                <?php echo __('Curriculum', 'relatos'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="padding1 color1p">
    <div class="container">
        <h3><b><?php _e('Other Information','relatos'); ?></b></h3>
        <br />
        <div class="row">
            <?php if ( $resource->collection ): ?>
                <div class="col-md-4 margin1">
                    <div class="box1 title1 h-100">
                        <h4><?php echo mb_strtoupper(__('Collection', 'relatos')); ?></h4>
                        <table class="table table-sm">
                            <tbody>
                                <?php foreach ($resource->collection as $collection) : ?>
                                    <tr>
                                        <td><?php echo $collection->name; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ( $resource->thematic_area ): ?>
                <?php if ( count($resource->thematic_area) == 1 && $resource->other_thematic_area ): ?>
                    <div class="col-md-4 margin1">
                        <div class="box1 title1 h-100">
                            <div class="box1-overflow">
                                <h4><?php echo mb_strtoupper(__('Thematic area', 'relatos')); ?></h4>
                                <?php echo $resource->other_thematic_area; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="col-md-4 margin1">
                        <div class="box1 title1 h-100">
                            <h4><?php echo mb_strtoupper(__('Thematic area', 'relatos')); ?></h4>
                            <div class="box1-overflow">
                                <table class="table table-sm">
                                    <tbody>
                                        <?php foreach ($resource->thematic_area as $thematic_area) : ?>
                                            <tr>
                                                <td><?php echo $thematic_area->name; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ( $resource->status ): ?>
                <div class="col-md-4 margin1">
                    <div class="box1 title1 h-100">
                        <h4><?php echo __('Experience status', 'relatos'); ?></h4>
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td><?php echo $status[$resource->status]; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-md-4 margin1">
                <div class="box1 title1 h-100">
                    <h4><?php echo mb_strtoupper(__('Dates', 'relatos')); ?></h4>
                    <?php if ( $resource->start_date ): ?>
                        <i class="fas fa-calendar-alt"></i> <?php echo __('Start', 'relatos') . ': ' . date('Y-m-d', strtotime($resource->start_date)); ?><br />
                    <?php endif; ?>
                    <?php if ( $resource->end_date && !$resource->is_current_date ): ?>
                        <i class="fas fa-calendar-alt"></i> <?php echo __('End', 'relatos') . ': ' . date('Y-m-d', strtotime($resource->end_date)); ?><br />
                    <?php endif; ?>
                </div>
            </div>
            <?php if ( $resource->country ): ?>
                <div class="col-md-4 margin1">
                    <div class="box1 title1 h-100">
                        <h4><?php echo mb_strtoupper(__('Country', 'relatos')); ?></h4>
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <!-- <td width="35"><img src="https://flagsapi.com/<?php echo $resource->country->code; ?>/flat/64.png" alt="" style="width: 30px;"></td> -->
                                    <td width="35"><img src="https://www.countryflagicons.com/FLAT/64/<?php echo $resource->country->code; ?>.png" alt="" style="width: 30px;"></td>
                                    <td><?php echo $resource->country->name; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ( $resource->region ): ?>
                <div class="col-md-4 margin1">
                    <div class="box1 title1 h-100">
                        <h4><?php echo mb_strtoupper(__('Region', 'relatos')); ?></h4>
                        <?php echo $resource->region; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ( $resource->city ): ?>
                <div class="col-md-4 margin1">
                    <div class="box1 title1 h-100">
                        <h4><?php echo mb_strtoupper(__('City', 'relatos')); ?></h4>
                        <?php echo $resource->city; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ( $resource->context ): ?>
                <div class="col-md-4 margin1">
                    <div class="box1 title1 h-100">
                        <h4><?php echo __('Location', 'relatos'); ?></h4>
                        <?php echo $resource->context; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ( $resource->population_group ): ?>
                <?php if ( count($resource->population_group) == 1 && $resource->other_population_group ): ?>
                    <div class="col-md-4 margin1">
                        <div class="box1 title1 h-100">
                            <h4><?php echo mb_strtoupper(__('Population', 'relatos')); ?></h4>
                            <?php echo $resource->other_population_group; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="col-md-4 margin1">
                        <div class="box1 title1 h-100">
                            <h4><?php echo mb_strtoupper(__('Population', 'relatos')); ?></h4>
                            <table class="table table-sm">
                                <tbody>
                                    <?php foreach ($resource->population_group as $population_group) : ?>
                                        <tr>
                                            <td><?php echo $population_group->name; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ( $resource->language ): ?>
                <div class="col-md-4 margin1 d-none">
                    <div class="box1 title1 h-100">
                        <h4><?php echo mb_strtoupper(__('Language', 'relatos')); ?></h4>
                        <?php echo $language[$resource->language]; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ( $resource->descriptors ) : ?>
                <?php $descriptors = json_decode($resource->descriptors, true); ?>
                <?php $descriptors = wp_list_pluck( $descriptors, 'value' ); ?>
                <div class="col-md-4 margin1">
                    <div class="box1 title1 h-100">
                        <h4><?php echo __('Descriptors', 'relatos'); ?></h4>
                        <?php echo implode('<br />', $descriptors); ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ( $resource->keywords ) : ?>
                <?php $keywords = json_decode($resource->keywords, true); ?>
                <?php $keywords = wp_list_pluck( $keywords, 'value' ); ?>
                <div class="col-md-4 margin1">
                    <div class="box1 title1 h-100">
                        <h4><?php echo __('Keywords', 'relatos'); ?></h4>
                        <?php echo implode('<br />', $keywords); ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ( $resource->related_links ) : $related_links = explode("\r\n", $resource->related_links); ?>
                <?php $related_links = array_map('trim', $related_links); ?>
                <div class="col-md-4 margin1 boxs-links">
                    <div class="box1 title1 h-100">
                        <h4><b><?php echo __('Related links', 'relatos'); ?></b></h4>
                        <?php foreach ($related_links as $link): ?>
                            <?php $_link = strpos($link, 'http') === 0 ? $link : "https://".$link; ?>
                            <?php if (filter_var($_link, FILTER_VALIDATE_URL) !== false) : ?>
                                <a href="<?php echo $_link; ?>" target="_blank">
                                    <i class="fa fa-external-link-square-alt" aria-hidden="true"> </i>
                                    <?php echo $_link; ?>
                                    <br />
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php $relatos_medias = get_relatos_attachment($response_json[0], 'others'); ?>
            <?php if ( $relatos_medias ) : ?>
                <div class="col-md-4 margin1">
                    <div class="box1 title1 h-100">
                        <h4><?php echo __('Other medias', 'relatos'); ?></h4>
                        <?php foreach ($relatos_medias as $uri): ?>
                            <?php $_uri = strpos($uri, 'http') === 0 ? $uri : "https://".$uri; ?>
                            <?php if (filter_var($_uri, FILTER_VALIDATE_URL) !== false) : ?>
                                <a href="<?php echo $_uri; ?>" target="_blank">
                                    <i class="far fa-file-alt" aria-hidden="true"> </i>
                                    <?php $filename = explode('_', basename($_uri)); ?>
                                    <?php echo end($filename); ?>
                                    <br />
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ( $resource->products_information ): ?>
                <div class="col-md-12 margin1">
                    <div class="box1 title1 h-100">
                        <h4><?php echo __('Products, materials and publications', 'relatos'); ?></h4>
                        <p><?php echo nl2br($resource->products_information); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ( $resource->notes ): ?>
                <div class="col-md-12 margin1">
                    <div class="box1 title1 h-100">
                        <h4><?php echo __('Notes', 'relatos'); ?></h4>
                        <p><?php echo nl2br($resource->notes); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ( $relatos_config['custom_color'] ) : ?>
    <style type="text/css">
        .box1 h4 { background: linear-gradient(328deg, <?php echo stripslashes($relatos_config['custom_color']); ?> 0%, #73a0be 100%); }
        .title2 { color: <?php echo stripslashes($relatos_config['custom_color']); ?>; }
        .title2::after { background: linear-gradient(328deg, #d2d2d2 0%, <?php echo stripslashes($relatos_config['custom_color']); ?> 100%); }
        #title-relatos { background: <?php echo $relatos_config['custom_color']; ?>; }
        #accordionArchive .card-header { border-left: 3px solid <?php echo stripslashes($relatos_config['custom_color']); ?>; }
    </style>
<?php endif; ?>

<script type="text/javascript">
    lightbox.option({
        'albumLabel': "<?php echo __('Image'); ?> %1 <?php echo __('of'); ?> %2",
    });
</script>

<?php get_footer(); ?>
