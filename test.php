<?php
// test_imagick.php
echo "PHP Version: " . phpversion() . "<br>";

if (extension_loaded('imagick')) {
    echo "✓ ImageMagick extension is loaded<br>";
    
    // Test ImageMagick version
    $imagick = new Imagick();
    $version = $imagick->getVersion();
    echo "ImageMagick Version: " . $version['versionString'] . "<br>";
    
    // List supported formats
    $formats = $imagick->queryFormats();
    echo "Supported formats: " . count($formats) . "<br>";
    
    echo "✓ ImageMagick is working correctly!";
} else {
    echo "✗ ImageMagick extension is NOT loaded<br>";
    
    // Check available extensions
    echo "Loaded extensions: " . implode(", ", get_loaded_extensions());
}
?>