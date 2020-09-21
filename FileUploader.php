<?php


namespace App\Classes\UploadImage;


use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileUploader
{
    // TODO: parametrlani tipini tekshirish
    private $mandatoryParams = ['file', 'owner', 'has_image'];

    private static $selfInstance;

    private $fileOwner;
    private $file;

    private $disc;
    private $filePath;

    private function __construct()
    {
    }

    public static function upload(array $config)
    {
        $uploaderInstance = self::getInstance();
        $uploaderInstance
            ->checkConfigs($config)
            ->setConfigs($config);

        $uploaderInstance->deleteIfExist($config['has_image']);
        return $uploaderInstance->isMultiple() ? $uploaderInstance->uploadMultiple() : $uploaderInstance->uploadSingle();
    }

    private function checkConfigs($configs)
    {
        array_walk($this->mandatoryParams, static function ($element) use ($configs) {
            if (!array_key_exists($element, $configs)) {
                throw new \InvalidArgumentException("'{$element}' parameter should be assigned");
            }
        });
        return $this;
    }

    private static function getInstance()
    {
        if (!self::$selfInstance) {
            self::$selfInstance = new self();
        }
        return self::$selfInstance;
    }

    public function setConfigs($config): void
    {
        $this->file = $config['file'];
        $this->fileOwner = $config['owner'];
        $this->disc = isset($config['disc']) ? $config['disc'] : 'public';


        $this->createUploadingFolder();
    }

    private function deleteIfExist($images)
    {
       if ($images && is_string($images)) {
           $images = explode(',', $images);
           foreach ($images as $image) {
               unlink(storage_path('app/' . $this->disc) . '/' . $image);
           }
       }
    }

    private function createUploadingFolder()
    {
        $reflector = new \ReflectionClass($this->fileOwner);
        $controllerName = $this->fetchNameFromNamespace($reflector->name);
        try {
            $this->createPath($controllerName);
            $this->filePath = $controllerName;
        } catch (FileException $e) {
            throw new FileException("{$controllerName} folder could not be created! " . $e->getMessage());
        }
    }

    private function fetchNameFromNamespace(string $name)
    {
        $controllerName = explode('\\', $name);
        $controllerName = str_replace('Controller', '', $controllerName);

        return Str::snake($controllerName[count($controllerName) - 1]);
    }

    private function createPath(string $folderName)
    {
        $path = storage_path('app/' . $this->disc) . '/' . $folderName;
        if (!File::isDirectory($path)) {
            Storage::disk($this->disc)->makeDirectory($folderName);
        }
        return $path;
    }

    private function isMultiple()
    {
        return is_array($this->file);
    }

    private function uploadSingle()
    {
        return $this->saveFile($this->file);
    }

    public function uploadMultiple()
    {
        $paths = [];
        foreach ($this->file as $file) {
            $paths[] = $this->saveFile($file);
        }
        return $paths;
    }

    public function generateFileName()
    {
        return time() . '_' . Str::random(25);
    }

    private function saveFile($file)
    {
        $fileName = $this->generateFileName() . '.' . $file->extension();
        return $file->storeAs($this->filePath, $fileName, $this->disc);
    }
}
