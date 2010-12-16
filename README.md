# ls-module-imageforge
LemonStand module that adds additional image manipulation options. In the future this will use an existing image manipulation library.

## Installation
1. Download ImageForge
1. Create a folder named `imageforge` in the `modules` directory.
1. Extract all files into the `modules/imageforge` directory (`modules/imageforge/README.md` should exist).
1. Done!

## Usage
Add two parameters to getThumbnailPath, and image_url requests. Example:  
	$product->getThumbnailPath(50, 50);  
to:  
	$product->getThumbnailPath(50, 50, true, array('mode' => 'zoom_fit'));  

## Technical

## Credit

## License
`ls-module-imageforge` is released under the MIT license. A copy of the MIT license can be found in the `LICENSE` file.
