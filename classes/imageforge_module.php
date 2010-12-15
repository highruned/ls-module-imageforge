<?

class ImageForge_Module extends Core_ModuleBase {
  protected function createModuleInfo() {
    $CONFIG['TRACE_LOG']['image'] = PATH_APP . '/logs/imageforge.txt';
    
    return new Core_ModuleInfo(
      "ImageForge",
      "Image manipulation",
      "Eric Muyser"
    );
  }
  
	public function subscribeEvents() {
		Backend::$events->addEvent('core:onProcessImage', $this, 'process_image');
	}

	public function process_image($file, $width, $height, $return_jpeg, $params) {
		if($params['mode'] != 'zoom_fit')
			return;
		
		$ext = $return_jpeg ? 'jpg' : 'png';

		$thumbnail_path = '/uploaded/thumbnails/' . implode('.', array_slice(explode('.', $file->name), 0, -1)) . '_' . $file->id . '_' . $width . 'x' . $height . '.' . $ext;
		$thumbnail_file = PATH_APP . $thumbnail_path;

		if(file_exists($thumbnail_file))
			return root_url($thumbnail_path);

		try {
			ImageForge_Helper::makeThumbnail($file->getFileSavePath($file->disk_name), $thumbnail_file, $width, $height, false, $params['mode'], $return_jpeg);
		}
		catch(Exception $ex) {
      throw $ex;
			@copy(PATH_APP . '/phproad/resources/images/thumbnail_error.gif', $thumbnail_file);
		}

		return root_url($thumbnail_path);
	}
}
