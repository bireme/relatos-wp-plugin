var $j = jQuery.noConflict();

$j(window).load(function(){
    showTips();
    show_more_list();
});

$j(document).ready(function(){
    $j('#bpImg').slick({
        dots: true,
        arrows: true,
        infinite: true,
        slidesToShow: 3,
        slidesToScroll: 3,
        responsive: [
        {
          breakpoint: 1024,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 3,
            // arrows: false,
            infinite: true,
            dots: true
          }
        },
        {
          breakpoint: 600,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 2,
            // arrows: false,
            infinite: true,
            dots: true
          }
        },
        {
          breakpoint: 480,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1,
            // arrows: false,
            infinite: true,
            dots: true
          }
        }
        // You can unslick at a given breakpoint now by adding:
        // settings: "unslick"
        // instead of a settings object
      ]
    });
});

$j(document).ready(function(){
    var list = $j('.table.list');

    list.each(function( i ) {
        var numToShow = 5;
        var button = $j(this).next().find('a.show-more');
        var items = $j('tr', this);
        var numInList = items.length;

        items.hide();
        if (numInList > numToShow) {
            button.show();
        }
        items.slice(0, numToShow).show();

        button.click(function(){
            var showing = items.filter(':visible').length;
            items.slice(showing - 1, showing + numToShow).fadeIn();
            var nowShowing = items.filter(':visible').length;
            if (nowShowing >= numInList) {
                button.hide();
            }
        });
    });
});

$j(document).ready(function(){
    $j("video").click(function() {
        var video = $j(this).get(0);
        $j("video").not(this).each(function() {
            $j(this).get(0).pause();
        });
    });
});

function change_count(elem) {
    var form = document.searchForm;
    form.count.value = elem.value;
    $j("#searchForm").submit();
}

function change_format(elem) {
    var form = document.searchForm;
    form.format.value = elem.value;
    $j("#searchForm").submit();
}

function change_sort(obj){
    var sort = obj.options[obj.selectedIndex].value;
    var form = document.searchForm;
    form.sort.value = sort;
    $j("#searchForm").submit();
}

function showTips(){
    $j('.tooltip').tooltipster({
        animation: 'fade',
    });
}

function showHideFilters(){
    $j('#filters').toggle();
}

function animateMenu(obj) {
    obj.classList.toggle("change");
}

function show_more_list(){
    $j('.more-items a').click(function() {
        var element = $j(this).parent().prev().children('.hide');
        if ( element.length ) {
            element.each(function( index ) {
                if ( index < 5 ) {
                      $j(this).removeClass('hide');
                } else {
                    return false;
                }
            });

            var el = $j(this).parent().prev().children('.hide');

            if ( !el.length ) {
                $j(this).parent().hide();
            }
        }
    });
}

function remove_filter(id) {
    // remove hidden field
    $j("#"+id).remove();
    var filter = '';

    $j('.apply_filter').each(function(i){
        filter += this.value + ' AND ';
    });
    // remove last AND of string
    filter = filter.replace(/\sAND\s$/, "");

    $j('#filter').val(filter);
    $j("#formFilters").submit();
}

function show_similar(url){
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("ajax").innerHTML = this.responseText;
        }else {
            document.getElementById("ajax").innerHTML = '<li class="cat-item"><div class="loader"></div></li>';
        }
    };
    xmlhttp.open("GET", url, true);
    xmlhttp.send();
}

function show_related(url){
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var loader = document.getElementById("loader");
            loader.parentNode.removeChild(loader);
            document.getElementById("async").innerHTML = this.responseText;

            $j('#async').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: false,
                autoplaySpeed: 3000,
                infinite: true,
                dots: false
            });
        }
    };
    xmlhttp.open("GET", url, true);
    xmlhttp.send();
}

function tabs(tab) {
    $j('.abstract-version').hide();
    $j('#tab-'+tab).show();
    $j('li').removeClass('active');
    $j('li').click(function(){
        $j(this).addClass('active');
    });
}
