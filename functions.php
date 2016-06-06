<?php
/*
Plugin Name: Grimage
Plugin URI: http://yourlocalwebmaster.com
Description: Filters all images in content and wraps in a grimage div (grant image) which launches the FB share onclick w/ THAT image loaded into the status window.
Author: Grant Kimball
Version: 1.5
Author URI: http://yourlocalwebmaster.com
 */

class Grimage
{
  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_grimage_admin_menus'));
    add_action('wp_loaded', array($this, 'my_front_end_stuff'));
  }

  /**
   * Handle front end hooks
   */
  public function my_front_end_stuff()
  {
    add_action('wp_enqueue_scripts', array($this, 'myScripts'));

    // Don't load the filters if the display option is toggled to NONE!
    if(get_option('grimage_display_options') != "none") {
      //add_filter('the_content', array($this, 'replaceImagesWithGrimages'));
      add_filter('the_content', array($this, 'replaceImagesWithGrimagesTwo'));
      add_action('wp_head', array($this, 'hook_js'));
      add_action('wp_head', array($this, 'hook_css'));
      add_action('wp_footer', array($this, 'grimage_modal'));
    }
  }

  /**
   * Enques my scripts (css and js).
   */
  public function myScripts()
  {
    //wp_register_script('angularjs',plugins_url('bower_components/angular/angular.min.js', __FILE__));
    wp_enqueue_style('grimagecss', plugins_url('style.css', __FILE__));
    //wp_enqueue_script('grimagescripts',plugins_url('/app.js',__FILE__));
  }

  /**
   * Handle admin menu insertion
   */
  public function register_grimage_admin_menus()
  {
    add_options_page('Grimage settings', 'Grimage', 'manage_options', 'grimage_options', array($this, 'grimage_options_page'));
  }

  /**
   * The grimage options page.
   */
  public function grimage_options_page()
  {
    if (isset($_POST['facebook_appid'])) {
      if ($this->process_grimage_update_page($_POST)) {
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

        <div style="margin-top:10px;">
          <label for="display_option"><strong>Display Option:</strong></label>
          <input type="radio" name="display_option" value="none" <?php if(get_option('grimage_display_option') == 'none') echo "checked"; ?> /> None <small>(deactivate plugin)</small>
          <input type="radio" name="display_option" value="under" <?php if(get_option('grimage_display_option') == 'under') echo "checked"; ?> /> Under <small>(link will be displayed below posts.)</small>
          <input type="radio" name="display_option" value="over" <?php if(get_option('grimage_display_option') == 'over') echo "checked"; ?> /> Over <small>(link will be displayed on hover)</small>
        </div>

        <div style="margin-top:10px;"><label for="facebook_appid"><strong>Facebook AppID:</strong></label><br/>
          <input type="text" name="facebook_appid" placeholder="app id" id="facebook_appid"
                 value="<?php if (get_option('grimage_facebook_appid')) echo get_option("grimage_facebook_appid"); ?>"/>
        </div>
        <div style="margin-top:10px;">
          <label for="modalcontent"><strong>Modal Content</strong></label><br/>
                    <textarea name="modalcontent" id="modalcontent" style="width:50%;height:400px;"><?php echo stripslashes(get_option('grimage_modalcontent')); ?></textarea>
        </div>
        <div style="margin-top:10px;">
          <label for="grimage_fb_linktext"><strong>FB Link Text</strong></label><br/>
                    <input type="text" name="grimage_fb_linktext" id="grimage_fb_linktext" style="" value="<?php echo stripslashes(get_option('grimage_fb_linktext')); ?>" />
        </div>

        <div style="margin-top:10px;">
          <label for="grimagecustomcss"><strong>Custom CSS</strong></label><br/>
                    <textarea name="grimagecustomcss" id="grimagecustomcss" style="width:50%;height:400px;"><?php echo stripslashes(get_option('grimage_grimagecustomcss')); ?></textarea>
        </div>

        <div style="margin-top:10px;">
          <button type="submit" class="button btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  <?php }

  /**
   * @param $post
   * @return bool
   * Process update form
   */
  public function process_grimage_update_page($post)
  {
    update_option('grimage_facebook_appid', $post['facebook_appid']);
    update_option('grimage_modalcontent', $post['modalcontent']);
    update_option('grimage_grimagecustomcss', $post['grimagecustomcss']);
    update_option('grimage_fb_linktext', $post['grimage_fb_linktext']);
    update_option('grimage_display_option', $post['display_option']);
    return true;
  }

  /**
   * @return string
   * The CSS that gets added to the head.  This is dynamic from the admin settings -> grimage settings.
   */
  public function hook_css()
  {?>
    <style type="text/css">
      /** Grimage CSS**/
      <?php
        echo get_option('grimage_grimagecustomcss');
      ?>
    </style>
  <?php }

  /**
   * The JS to get address to the header.
   */
  public function hook_js()
  {
    ?>
    <script>window.fbAsyncInit = function () {
        FB.init({
          appId: '<?php echo get_option('grimage_facebook_appid');?>',
          xfbml: true,
          version: 'v2.6'
        });
      };
      (function (d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {
          return;
        }
        js = d.createElement(s);
        js.id = id;
        js.src = "//connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
      }(document, 'script', 'facebook-jssdk'));

      (function ($) {
        $(document).ready(function () {
          function getCookie(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
              var c = ca[i];
              while (c.charAt(0) == ' ') {
                c = c.substring(1);
              }
              if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
              }
            }
            return "";
          }
          // bind it late for infinite scroll posts....
          $('.entry-content').on('click','.grimage .clicker',function (e) {
            console.log('sharing this page...'+$(e.currentTarget).data('url'));
            var image_url = $(e.currentTarget).siblings('img').attr('src');
            FB.ui({
              method: 'share',
              href: $(e.currentTarget).data('url'),
              //               title: 'This should be set in the meta headers..',
              picture: image_url,
              //               caption: 'This should be set in the meta headers..',
              //               description: 'This should be set in the meta headers..'
            }, function (response) {
              // Debug response (optional)
              //console.log(response);
              // Make sure the modal hasn't been shown in the last 7 days,  and make sure the modal hasn't already been clicked like.
              if (getCookie('grimage_modal_shown') == "" && getCookie('grimage_modal_liked') == "") {
                setTimeout(function () {
                  // click the modal button to show the modal :)
                  //$('#show_grimage_modal').click();
                  $('.grimage_modal .grimage_modal-dialog').css('-webkit-transform', 'translate(0, 0)');
                  $('.grimage_modal .grimage_modal-dialog').css('-ms-transform', 'translate(0, 0)');
                  $('.grimage_modal .grimage_modal-dialog').css('transform', 'translate(0, 0)');
                  $('.grimage_modal .grimage_modal-dialog').css('top', '20%');
                  $('.grimage_modalclose').click(function (e) {
                    e.preventDefault();

                    // User clicked the close button, so lets set a cookie to prevent the modal from popping up for a few days..
                    document.cookie = "grimage_modal_shown=true; expires=<?php echo date('D, d M Y', strtotime('+1 WEEK'));?> 12:00:00 UTC";
                    $('.grimage_modal').hide();
                  });
                }, 500);
              } // end if hide modal...
            });
            FB.Event.subscribe('edge.create', function (response) {
              document.cookie = "grimage_modal_liked=true; expires=<?php echo date('D, d M Y', strtotime('+1 YEAR'));?> 12:00:00 UTC";
              //console.log('like button clicked!');
              $('.grimage_modal').hide();
            });
          });
        });
      }(jQuery))</script>
    <?php
  }

  /**
   * @param $the_content
   * @return mixed
   * Parse the content and replace the images with the facebook share wrapper.
   */
  public function replaceImagesWithGrimagesTwo($the_content){
    global $post;
    $pattern = '/(<img[^>]*class=\"([^>]*?)\"[^>]*>)/i';
    $replacement = '<div class="grimage $2">$1<i class="fa fa-facebook clicker" data-url="'.get_permalink($post->ID).'">'.get_option("grimage_fb_linktext").'</i></div>';
    $the_content = preg_replace($pattern, $replacement, $the_content);
    return $the_content;
  }



  /**
   * The modal CTA HTML which gets injected into the footer and launched after the grimage share dialog has been closed. Note:: Doesn't display if the cookie is set... see JS
   */
  public function grimage_modal()
  {
    ?>
    <!-- Modal -->
    <div class="grimage_modal" id="grimage_modal-one" aria-hidden="true">
      <div class="grimage_modal-dialog">
        <div class="grimage_modal-header">
          <a href="#" class="btn-close grimage_modalclose" aria-hidden="true">×</a> <!--CHANGED TO "#close"-->
        </div>
        <div class="grimage_modal-body">
          <?php echo stripslashes(get_option('grimage_modalcontent')); ?>
        </div>
        <div class="grimage_modal-footer">

        </div>
      </div>
    </div>
    <!-- /Modal -->
  <?php }


}

new Grimage();
