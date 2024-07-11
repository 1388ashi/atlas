<?php
use Illuminate\Contracts\Http\Kernel;
use Symfony\Component\Finder\Finder;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';



foreach (Finder::create()->files()
             ->name("*.php")
             ->in(__DIR__.'/*/Database/Migrations/') as $file) {
    $fileContent = $file->getContents();

    $className = str_replace('.php', '', \Illuminate\Support\Str::studly(substr($file->getFilename(), '18'))) ;

    $path = $file->getPath();
    $a= strpos($path,'Modules/');
    $prefix = "C:\Projects\shop\\";
    $moduleName=  substr($path, $a+8, strpos($path, '/', $a+8)-($a+8));
    $namespace = str_replace('/','\\',str_replace($prefix, '', $path).';');
    $extendsName = 'Base'.$className;
    $useBaseClass = 'use Shetabit\Shopit\\'.'Database'."\\".$className.' as '.$extendsName.';';


    $content = "<?php
$useBaseClass

class $className extends $extendsName {}";


    file_put_contents($file->getPathname(), $content);
    touch(base_path('vendor/shetabit/shopit/src/Database/').\Illuminate\Support\Str::studly(substr($file->getFilename(), '18')));

    $newContent =  str_replace('<?php' , '<?php'.PHP_EOL.'namespace Shetabit\Shopit\Database;', $fileContent);
    $schemaStart =  strlen('function (Blueprint $table) {') + strpos($newContent, 'function (Blueprint $table) {');
    $schemaEnd   = strpos($newContent, '});') - 1;

    $body = substr($newContent , $schemaStart, $schemaEnd - $schemaStart);
    $newContent = str_replace('function (Blueprint $table) {' , 'function (Blueprint $table) {'.PHP_EOL. '            $this->default()($table);'.PHP_EOL, $newContent);
    $newContent = str_replace($body , '', $newContent);

    $default = "
    public function default()
    {
        return function (\$table) { $body };
    }
";
    $pos = strrpos($newContent, '}');
    $newContent = substr($newContent, 0, $pos) . $default . substr($newContent, $pos, 1);
    $newContent = str_replace('});' , '       });'.PHP_EOL, $newContent);





    file_put_contents(base_path('vendor/shetabit/shopit/src/Database/').\Illuminate\Support\Str::studly(substr($file->getFilename(), '18')), $newContent);




}
