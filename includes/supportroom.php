<?php

    if (!defined('ABSPATH')) exit;

    class ICONSFACTORY_SUPPORTROOM extends ICONSFACTORY {

        function __construct() {
            //
        }

        function room_template($page_slug) {

			$htm  = '<img width="150" src="https://svgsprite.com/public/images/artemy.jpg" alt="author" style="position: relative; left: 60px;">';

            $htm .= '<h3 class="'.ICNSFCTR_SLUG.'_h3">Hi</h3>';

            $htm .= '<p class="'.ICNSFCTR_SLUG.'_p">
                My name is Artemy. I\'m author of the plugin. You can send me a creative task or any question by using this contact form:
            </p>';

            $htm .= '<div class="'.ICNSFCTR_SLUG.'_empty_space"></div>';

            $request_options = array(
                array('Request fresh icons for free','Order - freebies', 'Without any deadlines! <br>Or check my <a href="https://svgsprite.com/special-offer.html" target="_blank">Special offer</a> 🔥'),
                array('Order personal icons', 'Order - paid images', '$15 per one vector image.<br>Or check my <a href="https://svgsprite.com/special-offer.html" target="_blank">Special offer</a> 🔥'),
                array('Submit an image collection', 'Publish collection', 'It\'s the opportunity to publish your collection officially with all the necessary contact information.<br><br>If you have a prepared set of icons (ZIP archive created by using <a href="'.ICNSFCTR_ADMIN_URL.'_uploadroom" target="_blank">the manager of collections</a>) or you have any raw icons, please, attach a link to your stuff (use Google Drive or Dropbox).<br><br>In the case of good icon design, I will add your set into the plugin library.'),
                array('Bug report', 'Bug report', 'Thanks for your signal, my friend!'),
                array('Other', 'Other', 'Attention! I\'m a bad English speaker ;)')
            );

            $subjects = array();
            $subjects_for_js = array();
            $notes_markup = '';
            foreach ($request_options as $key => $val) {
                $subjects[$val[0]] = $val[1];
                $subjects_for_js[] = $val[1];
                $notes_markup .= '<span'.($key>0?' style="display: none;"':'').'>'.$val[2].'</span>';
            }

            $form_content = $this->ui_row_template(
                $this->dropdown_template('subject', $subjects,
                    false,
                    false,
					false,
					false
                ),
                'Subject',
                -1,
                false,
                false,
				false,
				false
            );

            $form_content .= '<p class="'.ICNSFCTR_SLUG.'_p">
            <span id="'.ICNSFCTR_SLUG.'_notes">'.$notes_markup.'</span>
            </p>';

            $form_content .= $this->ui_row_template(
                $this->simple_input_template('sender', 'text', get_option('admin_email'), false, false, '@', false, false),
                'Email',
                -1,
                false,
                false,
				false,
				false
            );

            $form_content .= $this->ui_row_template(
                $this->simple_input_template('message', 'textarea', false, false, false, '...', false),
                'Message',
                -1,
                false,
                false,
				false,
				false
            );

            $form_content .= $this->ui_row_template(
                $this->btn_template('submit', array('narrow_btn'), false, 'SEND', false, false),
                false, -1, false, false, false, false
            );

			$htm .= $this->form_template('support', ICNSFCTR_ADMIN_URL.'_supportroom', $form_content);


			$htm .= '<br><h3 class="'.ICNSFCTR_SLUG.'_h3">Updates<h3>';

			$htm .= '<div class="'.ICNSFCTR_SLUG.'_p">
				Currently, there are two updating methods:
			</div>';

			$htm .= '<div class="'.ICNSFCTR_SLUG.'_p">
				<strong>1.</strong> Without saving of user presets and image collections! Go to <a class="'.ICNSFCTR_SLUG.'_link" href="'.admin_url('plugins.php?plugin_status=upgrade').'" target="_blank">plugins manager</a> to check updates by the ordinary way.
			</div>';

			$htm .= '<div class="'.ICNSFCTR_SLUG.'_p">
				<strong>2.</strong> Manual method. Make a backup of the files:<br> <code style="margin: 2px 0;border-radius: 3px;display: inline-block;">wp-content/plugins/icons-factory/</code><br>
				Download this <a href="https://downloads.wordpress.org/plugin/icons-factory.'.ICNSFCTR_VERSION.'.zip">ZIP archive</a>. Unpack it at local hard disk. Move the unpacked files to the plugin directory with the replacement of the existing ones.
			</div>';

			$htm .= '<div class="'.ICNSFCTR_SLUG.'_p">
				Clear all possible caches anyway.
			</div>';


			$htm .= '<br><h3 class="'.ICNSFCTR_SLUG.'_h3">Upcoming project. Beta testing<h3>';

			$htm .= '<div class="'.ICNSFCTR_SLUG.'_p">
				Free visual editor with a library of high-quality graphics elements created by talented visual artists. The project called <strong>"Reactive Doodles</strong>". This tool will be available as a cross-platform application and a WordPress plugin.
			</div>';

			$htm .= '<div class="'.ICNSFCTR_SLUG.'_p">
				It\'s really <strong>a new experience for WordPress users</strong>. You will can create professional illustrations for your website at the level of the presentations of Dropbox or Mailchimp by yourself inside the WordPress environment.
			</div>';

			$htm .= '<div class="'.ICNSFCTR_SLUG.'_p">
				Any serious design skills are not required. The plugin will not affect the speed of your website because the tool bakes usual images to the WP Media Library.
			</div>';

			$htm .= '<div id="mc_embed_signup" class="p">
				<form action="https://darkrender.us9.list-manage.com/subscribe/post?u=b64922c973994e1838ced4b7d&amp;id=08d7548232" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="betaForm" target="_blank" novalidate>

					<br>
					<div class="'.ICNSFCTR_SLUG.'_ui_title" data-role="ui_row_el" data-index="">
						Email
					</div>

					<div class="'.ICNSFCTR_SLUG.'_ui_fields" data-role="ui_row_el" data-index="">
						<input spellcheck="false" class="'.ICNSFCTR_SLUG.'_field" name="EMAIL" type="text" placeholder="@" id="'.ICNSFCTR_SLUG.'_sender" value="'.get_option('admin_email').'">
					</div>

					<div class="'.ICNSFCTR_SLUG.'_ui_fields" data-role="ui_row_el" data-index="">
						<button name="subscribe" class="'.ICNSFCTR_SLUG.'_btn '.ICNSFCTR_SLUG.'_follow_btn '.ICNSFCTR_SLUG.'_narrow_btn">FOLLOW</button>
					</div>

					<div style="position: absolute; left: -5000px;" aria-hidden="true">
						<input type="text" name="b_b64922c973994e1838ced4b7d_08d7548232" tabindex="-1" value="">
					</div>

				</form>
			</div>';


			$htm .= '<div class="'.ICNSFCTR_SLUG.'_p '.ICNSFCTR_SLUG.'_upcoming_projects"><img width="860" src="'.ICNSFCTR_URL.'/img/upcoming.png" alt="Reactive Doodles - WirdPress Graphics editor" title="Reactive Doodles - WirdPress Graphics editor"></div>';




            $htm .= $this->init_js_module($page_slug, array(
                'subjects' => $subjects_for_js
            ));

            return $this->layer_template($htm, 'support', false, 1, false);
        }


    }

?>
