<?php
/*
Plugin Name: Grimage
Plugin URI: http://yourlocalwebmaster.com
Description: Filters all images in content and wraps in a grimage span (grant image) which launches the FB share onclick w/ THAT image loaded into the status window. Apply a class of "nogrimage" to the image you do not want this to occur on.
Author: Grant Kimball
Version: 1.0
Author URI: http://yourlocalwebmaster.com
 */

class Grimage
{
  public function __construct()
  {
    add_action('wp_enqueue_scripts', array($this, 'myScripts'));
    add_filter('the_content',array($this,'replaceImagesWithGrimages'));
    add_action('wp_head',array($this,'hook_js'));
    add_action('wp_footer',array($this,'grimage_modal'));
    add_action('admin_menu',array($this,'register_grimage_admin_menus'));
  }

  public function myScripts()
  {
    //wp_register_script('angularjs',plugins_url('bower_components/angular/angular.min.js', __FILE__));
    wp_enqueue_style ( 'grimagecss',plugins_url('style.css', __FILE__));
    //wp_enqueue_script('grimagescripts',plugins_url('/app.js',__FILE__));
  }

    public function register_grimage_admin_menus(){
        //string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', string $icon_url = '', int $position = null
        add_menu_page('Grimage settings','Grimage Settings','manage_options','grimage_options',array($this,'grimage_options_page'));
    }

    public function grimage_options_page(){
        if(isset($_POST['facebook_appid'])){
            if($this->process_grimage_update_page($_POST)){
    ?>
                <div class="notice notice-success is-dismissible">
                    <p>Done!</p>
                </div>
                <?php
            }
        }
        ?>
        <div class="wrap">
            <h2>Grimage Settings</h2>
            <form name="grimage_form" method="post" action="">
                <div><label for="facebook_appid">Facebook AppID:</label><br/>
                <input type="text" name="facebook_appid" placeholder="app id" id="facebook_appid" value="<?php if(get_option('grimage_facebook_appid')) echo get_option("grimage_facebook_appid");?>" />
                </div>
                <div>
                    <label for="modalcontent">Modal Content</label><br/>
                    <textarea name="modalcontent" id="modalcontent" style="width:50%;height:400px;">
                    <?php //todo:: make this work! ?>
                    <?php echo stripslashes(get_option('grimage_modalcontent'));?>
                </textarea>
                </div>

                <div><button type="submit" class="button btn btn-primary">Update</button></div>
            </form>
        </div>
    <?php }




  public function process_grimage_update_page($post){
          update_option('grimage_facebook_appid',$post['facebook_appid']);
          update_option('grimage_modalcontent',$post['modalcontent']);
          return true;
  }
  public function hook_js(){
      ?><script>window.fbAsyncInit = function() {
    FB.init({
        appId       : '<?php echo get_option('grimage_facebook_appid');?>',
        xfbml      : true,
        version    : 'v2.6'
    });
};
(function(d, s, id){
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {return;}
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

(function ( $ ){
    $(document).ready(function(){

        $('.grimage .clicker').click(function(e){
            var image_url = $(e.currentTarget).siblings('img').attr('src');
            FB.ui({
                method: 'share',
                href: '<?php echo get_permalink();?>',
 //               title: 'This should be set in the meta headers..',
                picture: image_url,
 //               caption: 'This should be set in the meta headers..',
 //               description: 'This should be set in the meta headers..'
            }, function(response){
                // Debug response (optional)
                console.log(response);
                setTimeout(function(){
                    // click the modal button to show the modal :)
                    //$('#show_grimage_modal').click();
                    $('.grimage_modal .grimage_modal-dialog').css('-webkit-transform','translate(0, 0)');
                    $('.grimage_modal .grimage_modal-dialog').css('-ms-transform','translate(0, 0)');
                    $('.grimage_modal .grimage_modal-dialog').css('transform','translate(0, 0)');
                    $('.grimage_modal .grimage_modal-dialog').css('top','20%');
                    $('.grimage_modalclose').click(function(e){
                        e.preventDefault();
                        $('.grimage_modal').hide();
                    });
                },500);

            });
        });
      FB.Event.subscribe('edge.create', function(response) {
        console.log('like button clicked!');
        $('.grimage_modal').hide();
      });

    });
}(jQuery))</script>
     <?php
  }

  public function replaceImagesWithGrimages($the_content){
    // This will hide the share button from index/archive pages..
    if(!is_single()) return $the_content;

    $html = $the_content;
    $dom = new DOMDocument('1.0','utf-8');
    $dom->loadHTML($html);
    $span = $dom->createElement('span');
    $span->setAttribute('class', 'grimage');
    //$span->setAttribute('onClick', 'shareThisImage()');
    $images = $dom->getElementsByTagName('img');
    foreach ($images as $image) {
      // don't do it for images flagged nogrimage
      if (strpos($image->getAttribute('class'), 'nogrimage') === false) {
        $span_clone = $span->cloneNode();
        $image->parentNode->replaceChild($span_clone, $image);
        $span_clone->appendChild($image);
        $fbbutton = $dom->createElement('i',' SHARE (placeholder) &uarr;');
        $fbbutton->setAttribute('class','fa fa-facebook clicker');
        $span_clone->appendChild($fbbutton);
      }
    }
    $html = $dom->saveHTML();
    return $html;
  }
  public function grimage_modal(){?>
      <!-- Modal -->
      <div class="grimage_modal" id="grimage_modal-one" aria-hidden="true">
          <div class="grimage_modal-dialog">
              <div class="grimage_modal-header">
                  <a href="#" class="btn-close grimage_modalclose" aria-hidden="true">×</a> <!--CHANGED TO "#close"-->
              </div>
              <div class="grimage_modal-body">
                    <?php echo stripslashes(get_option('grimage_modalcontent'));?>
              </div>
              <div class="grimage_modal-footer">

              </div>
          </div>
      </div>
      <!-- /Modal -->
  <?php }


}
new Grimage();