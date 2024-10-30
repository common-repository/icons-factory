<?php

    if (!defined('ABSPATH')) exit;

    class ICONSFACTORY_CLN_MANAGER extends ICONSFACTORY {

        function __construct() {
            // var_dump($_FILES);
            // var_dump($_POST);
        }

        // MARKUP: COLLECTIONS MANAGER

        function template($notice, $clns_selector) {
            $fields = array(
                'title'   => array('Title*', 'Title of collection', 'text', false),
                'version' => array('Version*', '1.0', 'text', false),
                'author'  => array('Author*', 'A short and catchy nickname', 'text', false),
                'email'   => array('Contact email', 'To get new clients', 'text', false),
                'license' => array('Link to license', 'http://creativecommons.org/licenses/by/3.0/', 'text', false),
                'uri'     => array('Link to your web resource*', 'E.g. the link to your digital store', 'text', false),
                'cln_id'  => array(false, false, 'hidden', false),
                'as_blank'=> array(false, false, 'hidden', 1),
                'action'  => array(false, false, 'hidden', false),

                // Data for the "Quick file downloader"
                'data_format' => array(false, false, 'hidden', 'dir'),
                'content' => array(false, false, 'hidden', false), // = cln_id
                'content_type' => array(false, false, 'hidden', 'zip'),
                'content_parent_dir' => array(false, false, 'hidden', ICNSFCTR_LIBRARY)
            );

            $btns = array(
                'save_cln'    => array('SAVE', array()),
                'render_cln'  => array('SHOW IMAGES', array('hidden')),
                'del_cln'     => array('DEL', array('hidden')),
                'dwnld_cln'   => array(false, array('hidden','right'))
            );

            $cln_data_model = $clns_selector[1];

            $left = $this->ui_row_template(
                $clns_selector[0],
                'Choose an collection to edit it or create a new', -1, false, false, false, false
            );

            unset($clns_selector);

            foreach ($fields as $key => $value) {
                $input_htm = $this->simple_input_template($key, $value[2], $value[3], false, false, $value[1], false);
                $left .= $value[2]==='text' ? $this->ui_row_template($input_htm, $value[0], -1, false, false, false, false) : $input_htm;
            }

            $temp_btns_arr = array();
            foreach ($btns as $key => $value) {
                $temp_btns_arr[] = $this->btn_template($key, array_merge(array('narrow_btn'), $value[1]), array('act'=>$key), $value[0], false, false);
            }

            $left .= $this->ui_row_template(
                $this->btns_block_template($temp_btns_arr),
                '&nbsp;', -1, false, false, false, false
            );

            unset($temp_btns_arr);

            $left .= $this->multi_init_js_modules(
                array(
                    array(
                        'library',
                        false
                    ),
                    array(
                        'cln_manager',
                        array(
                            'cln_data' => $cln_data_model,
                            'cln_fields_data' => $fields,
                            'cln_btns_data' => $btns,
                            'notice'=> $notice
                        )
                    ),
                )
            );

            $right =  $this->ui_row_template(
                $this->logo_cln_template(false, false),
                'Logo', -1, false, false, false, false
            );

            return '<h3 class="'.ICNSFCTR_SLUG.'_h3">Collections manager</h3>
                <form enctype="multipart/form-data" method="post" class="'.ICNSFCTR_SLUG.'_upload_form" action="'.ICNSFCTR_ADMIN_URL.'_uploadroom" id="'.ICNSFCTR_SLUG.'_cln_manager_form">'.
                    $this->grid_template(
                        array($left),
                        array($right),
                        '50_50',
                        false
                    ).
                '</form>';
        }

        // ACTIONS: COLLECTIONS MANAGER

		function save_cln($data) {

			$response_mess = '';
            $response_type = 0; // 0 - success, 1 - failure
            $alert_mess = 'Oops, unable to save the collection. Call to Guru, please.';
            $error = false;

			$white_list_attrs = array('title','version','author','email','license','uri','cln_id','as_blank','action','data_format','content','content_type','content_parent_dir');

			foreach ($data as $key => $value) {
				if(!in_array($key, $white_list_attrs)) {
					$error = 'A disallowed key inside the collection information';
					break;
				} else {
					if(!$this->is_valid_content($value)) {
						$error = 'A disallowed value inside the collection information';
						break;
					} else {

						switch ($key) {

							case 'email':
								$data['email'] = sanitize_email($data['email']);
								break;

							case 'uri':
							case 'content_parent_dir':
								$data[$key] = $data[$key] ? esc_url($data[$key]) : '';
								break;

							default:
								$data[$key] = sanitize_text_field(
									preg_replace('/\'|\"|`|&quot;|\\\/', '', $data[$key])
								);
								break;
						}

					}
				}
			}


            // Get collection ID from the request data or generate a new ID
            if(isset($data['cln_id']) && $data['cln_id']!=='false') {
                $cln_id = sanitize_text_field($data['cln_id']);
            } else {
                if(isset($data['title']) && isset($data['version']) && isset($data['author'])) {
                    $cln_id = $this->string_to_sort_hash(
						sanitize_text_field(
							sanitize_text_field($data['author']).sanitize_text_field($data['title']).sanitize_text_field($data['version'])
						)
					);
                } else $cln_id = false;
			}


            if($cln_id) {

                // Dir of actual collection
                $cln_dir = ICNSFCTR_LIBRARY.$cln_id;

                // Save blank collection? or Update existed?
                $as_blank = $data['as_blank'];
                $cln_exists = is_dir($cln_dir);

                // Check logo file
                $logo_file = $_FILES['logo'];
                $save_logo = $logo_file['size'] != 0 && $logo_file['error'] == 0;

                // Prepare data model for saving
                unset($data['cln_id']);
                unset($data['action']);
                unset($data['as_blank']);

                // Make a new folder if it's needful
                if($as_blank && !$cln_exists) {
                    $dir_exists = mkdir($cln_dir, 0700);
                    if(!$dir_exists) $error = 'Making of a folder';
                } else $dir_exists = true;

                if($dir_exists) {

                    // Save the new logo if it's uploaded
                    if($save_logo) {
                        if(!$this->save_cln_logo_file($cln_dir, $logo_file['tmp_name'], $logo_file['type'])) $error = 'Logo saving';
                    }

                    // Save the info file
                    if(!$this->save_cln_data_file($data, $cln_dir)) $error = 'Data file saving';

                }


            } else {
                $error = 'Incorrect ID';
            }

            return array($error, 'collection', 'save', 'saved');

        }

        function save_cln_data_file($data, $cln_dir) {
            unset($data['data_format']);
            unset($data['content']);
            unset($data['content_type']);
            unset($data['content_parent_dir']);
            return $this->save_array_to_file($data, $cln_dir.'/info.php');
        }


        function save_cln_logo_file($cln_dir, $tmp_name, $type) {
            if($type) {
                if($this->get_file_type($type, true)==='png') {
					$new_name = $cln_dir.'/logo.png';
                    if(move_uploaded_file($tmp_name, $new_name)) {
                        return true;
                    } else return false;
                }
            } else return false;
        }

        // Delete collection
        function del_cln($data) {

			$error = false;

			$cln_id = isset($data['cln_id'])
				? sanitize_text_field($data['cln_id'])
				: false;

				if($cln_id) {

                // Dir of actual collection
                $cln_dir = ICNSFCTR_LIBRARY.$cln_id;

                // Check the dir
                if(is_dir($cln_dir)) {
                    if(!$this->rmdir_recursive($cln_dir, false)) $error = 'Directory removing';
                } else {
                    $error = 'Collection dir is not existed';
                }

            } else {
                $error = 'Incorrect ID';
            }
            return array($error, 'collection', 'delete', 'deleted');
        }

        // Download collection
        function dwnld_cln() {
            // This action is intercepted in the primary constructor
        }


        // Upload premade collection in a zip
        function upload_cln($data) {
            $error = false;
			$target_dir = ICNSFCTR_LIBRARY;

			// Upload zip file
			// Filename, extention and file type will be sanitized in the next step
			// inside the "upload_files" function
            $zip_name = $this->upload_files(
				$target_dir,
				$_FILES['upload_cln'],
				array('zip'),
				false,
				false
			);

            // Unzip it and validate files content
            if($zip_name) {
                $zip_link = $target_dir.$zip_name.'.zip';
                if(!$this->unzip($zip_link, $target_dir.$zip_name, true)) {
					$error = 'Incorrect unzip operation';
				} else {

					$scan = scandir($target_dir.$zip_name);

					$black_list = array('.','..','.DS_Store','','__MACOSX');
					$invalid_files = array();

					foreach ($scan as $file) {
						if(!in_array($file, $black_list)) {

							$file_name_with_ext = explode('.',sanitize_file_name($file));
							$file_name = $file_name_with_ext[0];
							$file_ext = $file_name_with_ext[1];

							$file_path = $target_dir.$zip_name.'/'.$file;

							// Detect and remove any inner folders!
							if(is_dir($file_path)) {
								$invalid_files[] = $file_name;
								$this->rmdir_recursive($file_path, false);
							} else {

								// Check PNG content
								if($file_ext === 'png') {
									if(!$this->is_valid_png($file_path)) {
										$invalid_files[] = $file_name.'.png';
										unlink($file_path);
									}
								// Check Icon model file content
								} elseif ($file_ext === 'php') {
									if(!$this->is_valid_icon_model($file_path)) {
										$invalid_files[] = $file_name.'.php';
										unlink($file_path);
									}
								} else {
									unlink($file_path);
								}
							}

						}
					}

					if(count($invalid_files) > 0) {
						$error = 'Error! Please check your file[s]: \"'.( implode($invalid_files,', ')).'\"';
					}

					unlink($zip_link);
				}
            } else $error = 'Incorrect file moving operation';

            return array($error, 'file', 'upload', 'uploaded');
        }


    }

?>
