<?

class ImageForge_Helper
{
	/**
	 * Creates a thumbnail
	 * @param string $srcPath Specifies a sources image path
	 * @param string $destPath Specifies a destination image path
	 * @param mixed $destWidth Specifies a destination image width. Can have integer value or string 'auto'.
	 * @param mixed $destHeight Specifies a destination image height. Can have integer value or string 'auto'.
	 * @param string $mode Specifies a scaling mode. Possible values: keep_ratio, fit. It works only if both width and height are specified as numbers.
	 * @param string $returnJpeg - returns JPEG (if true) or PNG image
	 */
	public static function makeThumbnail($src_path, $dest_path, $dest_width, $dest_height, $forceGd = false, $mode = 'keep_ratio', $return_jpeg = true)
	{
		$extension = null;
		$pathInfo = pathinfo($src_path);
		if(isset($pathInfo['extension']))
			$extension = strtolower($pathInfo['extension']);

		$allowedExtensions = array('gif', 'jpeg', 'jpg','png');
		if(!in_array($extension, $allowedExtensions))
			throw new Phpr_ApplicationException('Unknown image format');

		if(!preg_match('/^[0-9]*!?$/', $dest_width) && $dest_width != 'auto')
			throw new Phpr_ApplicationException("Invalid width specifier. Please use integer or 'auto' value.");

		if(!preg_match('/^[0-9]*!?$/', $dest_height) && $dest_height != 'auto')
			throw new Phpr_ApplicationException("Invalid height specifier. Please use integer or 'auto' value.");

		list($src_width, $src_height) = getimagesize($src_path);

		$src_ratio = $src_width / $src_height;

		$width = $dest_width;
		$height = $dest_height;
		$x = $src_y = 0;
		$y = $src_x = 0;
		$center_image = false;

		if($dest_width == 'auto' && $dest_height == 'auto')
		{
			$width = $src_width;
			$height = $src_height;
		}
		elseif($dest_width == 'auto' && $dest_height != 'auto')
		{
			if(substr($dest_height, -1) == '!')
			{
				$height = substr($dest_height, 0, -1);
			}
			else
				$height = $src_height > $dest_height ? $dest_height : $src_height;

			$width = $dest_height * $src_ratio;
		}
		elseif($dest_height == 'auto' && $dest_width != 'auto')
		{
			if(substr($dest_width, -1) == '!')
				$width = substr($dest_width, 0, -1);
			else
				$width = $src_width > $dest_width ? $dest_width : $src_ratio;

			$height = $dest_width / $src_ratio;
		}
		else
		{
			$dest_ratio = $dest_width / $dest_height;

			if($mode == 'keep_ratio')
			{
				if($dest_width / $dest_height > $src_ratio)
				{
					$width = $dest_height * $src_ratio;
					$height = $dest_height;
				}
				else
				{
					$height = $dest_width / $src_ratio;
					$width = $dest_width;
				}

				$center_image = true;
			}
			else if($mode == 'zoom_fit')
			{
				if($dest_width > $dest_height)
				{
					$a_height = $src_height;
					$a_height = $dest_height / $dest_width * $src_width;

					if($a_height > $src_height)
					{
						$a_width = $dest_width / $dest_height * $src_height;

						if($a_width > $src_width)
						{
							$src_x = ($a_width - $src_width) / 2;
							$src_width = $a_width;
						}
					}
					else
					{
						$src_y = ($src_height - $a_height) / 2;
						$src_height = $a_height;
					}
				}
				else if($dest_height > $dest_width)
				{
					$a_width = $src_width;
					$src_width = $dest_width / $dest_height * $src_height;

					$l_width = $src_width > $a_width ? $a_width : $src_width;
					$h_width = $src_width > $a_width ? $src_width : $a_width;

					$src_x = ($h_width - $l_width) / 2;

					if($a_width > $src_width)
					{
						$a_height = $dest_height / $dest_width * $src_width;

						if($a_height > $src_height)
						{
							$src_y = ($a_height - $src_height) / 2;
							$src_height = $a_height;
						}
					}
					else
					{
						$src_x = ($src_width - $a_width) / 2;
						$src_width = $a_width;
					}
				}
				else
				{
					if($src_width > $src_height)
					{
						$a_width = $src_height;

						$src_x = ($src_width - $a_width) / 2;

						$src_width = $a_width;
					}
					else if($src_height > $src_width)
					{
						$a_height = $src_width;

						$src_y = ($src_height - $a_height) / 2;

						$src_height = $a_height;
					}
				}
			}
		}

		if($center_image)
		{
			$x = ceil($dest_width / 2 - $width / 2);
			$y = ceil($dest_height / 2 - $height / 2);
		}

		if(!Phpr::$config->get('IMAGEMAGICK_ENABLED') || $forceGd)
		{
			$image_p = imagecreatetruecolor($width, $height);

			$image = self::create_image($extension, $src_path);
			if($image == null)
				throw new Phpr_ApplicationException('Error loading the source image');

			if(!$return_jpeg)
			{
				imagealphablending($image_p, false);
				imagesavealpha($image_p, true);
			}

			$white = imagecolorallocate($image_p, 255, 255, 255);
			imagefilledrectangle($image_p, 0, 0, $width, $height, $white);

			imagecopyresampled($image_p, $image, $x, $y, $src_x, $src_y, $width, $height, $src_width, $src_height);

			if($return_jpeg)
				imagejpeg($image_p, $dest_path, Phpr::$config->get('IMAGE_JPEG_QUALITY', 70));
			else
				imagepng($image_p, $dest_path, 8);

			imagedestroy($image_p);
			imagedestroy($image);
		}
		else {
			self::im_resample($src_path, $dest_path, $width, $height, $src_width, $src_height, $return_jpeg);
		}
	}
	
	private static function create_image($extension, $srcPath)
	{
		switch ($extension) 
		{
			case 'jpeg' :
			case 'jpg' :
				return @imagecreatefromjpeg($srcPath);
			case 'png' : 
				return @imagecreatefrompng($srcPath);
			case 'gif' :
				return @imagecreatefromgif($srcPath);
		}
		
		return null;
	}
	
	private static function im_resample($origPath, $destPath, $width, $height, $imgWidth, $imgHeight, $returnJpeg = true)
	{
		try
		{
			$currentDir = 'im'.(time()+rand(1, 100000));
			$tmpDir = PATH_APP.'/temp/';
			if (!file_exists($tmpDir) || !is_writable($tmpDir))
				throw new Phpr_SystemException('Directory '.$tmpDir.' is not writable for PHP.');

			if ( !@mkdir($tmpDir.$currentDir) )
				throw new Phpr_SystemException('Error creating image magic directory in '.$tmpDir.$currentDir);

			@chmod($tmpDir.$currentDir, Phpr_Files::getFolderPermissions());
			
			$imPath = Phpr::$config->get('IMAGEMAGICK_PATH');
			$sysPaths = getenv('PATH');
			if (strlen($imPath))
			{
				$sysPaths .= ':'.$imPath;
				putenv('PATH='.$sysPaths);
			}

			$outputFile = './output';
			$output = array();
			
			chdir($tmpDir.$currentDir);

			if (strlen($imPath))
				$imPath .= '/';

			$JpegQuality = Phpr::$config->get('IMAGE_JPEG_QUALITY', 70);

			// if ($imgWidth != $width || $imgHeight != $height)
			// {
			if ($returnJpeg)
				$str = '"'.$imPath.'convert" "'.$origPath.'" -antialias -quality '.$JpegQuality.' -thumbnail "'.$imgWidth.'x'.$imgHeight.'>" -bordercolor white -border 1000 -gravity center -crop '.$imgWidth.'x'.$imgHeight.'+0+0 +repage JPEG:'.$outputFile;
			else
				$str = '"'.$imPath.'convert" "'.$origPath.'" -antialias -background none -thumbnail "'.$imgWidth.'x'.$imgHeight.'>" -gravity center -crop '.$imgWidth.'x'.$imgHeight.'+0+0 +repage PNG:'.$outputFile;

			// } else
			// 	$str = '"'.$imPath.'/convert" '.$origPath.' -background white -quality 90 -antialias -strip -geometry '.$width.'x'.$height.' JPEG:'.$outputFile;

			$Res = shell_exec($str);

			$resultFileDir = $tmpDir.$currentDir;

			$file1Exists = file_exists($resultFileDir.'/output');
			$file2Exists = file_exists($resultFileDir.'/output-0');

			if (!$file1Exists && !$file2Exists)
				throw new Phpr_SystemException("Error converting image with ImageMagick. IM command: \n\n".$str."\n\n");

			if ($file1Exists)
				copy($resultFileDir.'/output', $destPath);
			else	
				copy($resultFileDir.'/output-0', $destPath);
				
			if (file_exists($destPath))
				@chmod($destPath, Phpr_Files::getFilePermissions());
			
			if (file_exists($tmpDir.$currentDir))
				Phpr_Files::removeDir($tmpDir.$currentDir);
		}
		catch (Exception $ex)
		{
			if (file_exists($tmpDir.$currentDir))
				Phpr_Files::removeDir($tmpDir.$currentDir);

			throw $ex;
		}
	}

	/**
	 * Returns a thumbnail file name, unique for a specified 
	 * original file location, file modification time, thumbnail size and scaling mode
	 * @param string $path Specifies a source image path.
	 * @param mixed $width Specifies a thumbnail width. Can have integer value or string 'auto'.
	 * @param mixed $height Specifies a thumbnail height. Can have integer value or string 'auto'.
	 * @param string $mode Specifies a scaling mode. 
	 * @return string
	 */
	public static function createThumbnailName($path, $width, $height, $mode = 'keep_ratio')
	{
		return md5(dirname($path)).basename($path).'_'.filemtime(PATH_APP.$path).'_'.$width.'x'.$height.'_'.$mode.'.jpg';
	}

	/**
	 * Deletes thumbnails of a specified image
	 * @param string $path Specifies a source image path.
	 */
	public static function deleteImageThumbnails($path)
	{
		$thumbName = md5(dirname($path)).basename($path).'_*.jpg';

		$thumbPath = PATH_APP.'/uploaded/thumbnails/'.$thumbName;
		$thumbs = glob($thumbPath);
		if (is_array($thumbs))
		{
			foreach ($thumbs as $filename) 
			    @unlink($filename);
		}

	}
}