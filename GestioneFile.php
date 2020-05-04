<?php

/**
 * Classe per operazioni su file
 *
 * @author Matteo Ferrone
 * @since 2020-03-05
 * @version 1.9
 */
class GestioneFile {

    /**
     * Create file
     *
     * @param string $file File with path
     * @param string $message Text of the file
     * @param string $ext Extension of the file
     * @param string $mode Mode for creation
     * @return void
     */
    public function createFile($file, $message, $ext, $mode = 'w') {
        $f = fopen($file . $ext, $mode);
        fwrite($f, $message);
        fclose($f);
    }

    /**
     * Generic function for upload
     *
     * Example usage:
     *
     * $fileTmpName = $_FILES["fileUp"]["tmp_name"];
     * $fileName = $_FILES["fileUp"]["name"];
     * $fileSize = $_FILES["fileUp"]["size"];
     * $fileType = $_FILES["fileUp"]["type"];
     * $maxSize = 102400;
     * $typeArray = array(
     * 'pdf' => 'application/pdf',
     * 'doc' => 'application/msword',
     * 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
     * 'ppt' => 'application/vnd.ms-powerpoint',
     * 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
     * 'xls' => 'application/vnd.ms-excel',
     * 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
     * 'png' => 'image/png',
     * 'jpeg' => 'image/jpeg',
     * 'jpg' => 'image/jpg'
     * );
     * $gestioneFile = new GestioneFile();
     * echo $gestioneFile->uploadFile('../../FILE_UPLOADS/', $fileTmpName, $fileName, $fileSize, $fileType, $typeArray, $maxSize, $fileName);
     *
     * @param string $dirUpload Directory to upload
     * @param string $fileTmpName Temp file name
     * @param string $fileName File name
     * @param string $fileSize File size
     * @param string $fileType File type
     * @param string $typeArray Extensions allowed
     * @param string $maxSize Max size for uploading file
     * @param string $nome Final name
     * @return string Result of the operation
     */
    public function uploadFile($dirUpload, $fileTmpName, $fileName, $fileSize, $fileType, $typeArray, $maxSize, $nome) {
        $fileSizeMB = $fileSize / 1024;
        if (is_uploaded_file($fileTmpName)) {
            if (!in_array($fileType, $typeArray)) {
                http_response_code(404);
                return json_encode(
                    array(
                        "res" => "ko",
                        "message" => "Il file non è tra quelli ammessi!",
                        "nome_file" => $nome,
                        "tipo" => ""
                    )
                );
            } elseif ($fileSizeMB > $maxSize) {
                http_response_code(404);
                return json_encode(
                    array(
                        "res" => "ko",
                        "message" => "Il file è troppo grande!",
                        "nome_file" => $fileName,
                        "tipo" => ""
                    )
                );

            } else {
//                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
//                if (move_uploaded_file($fileTmpName, $dirUpload . $nome . '.' . $ext)) {
                if (move_uploaded_file($fileTmpName, $dirUpload . $nome)) {
                    http_response_code(200);
                    return json_encode(
                        array(
                            "res" => "ok",
                            "message" => "File caricato ' . $dirUpload . $nome . '!",
                            "nome_file" => $nome,
                            "tipo" => mime_content_type($dirUpload . $nome)
                        )
                    );
                } else {
                    http_response_code(404);
                    return json_encode(
                        array(
                            "res" => "ko",
                            "message" => "Impossibile caricare il file ' . $fileName . '!",
                            "nome_file" => $fileName,
                            "tipo" => ""
                        )
                    );
                }
            }
        } else {
            http_response_code(404);
            return json_encode(
                array(
                    "res" => "ko",
                    "message" => "Si è verficato un errore o non è stato inviato nessun file!",
                    "nome_file" => $fileName,
                    "tipo" => ""
                )
            );
        }
    }

    /**
     * Open a file
     *
     * @param $file
     * @param bool $doSort
     * @return array
     */
    public function openFile($file, $doSort = true) {
        $f = new SplFileInfo($file);
        $result = array();
        if ($f->isFile()) {
            $openFile = $f->openFile('r');
            while (!$openFile->eof()) {
                array_push($result, $openFile->fgets());
            }
        } else {
            array_push($result, 'Il file ' . $f->getBasename() . ' non esiste!');
        }

        if ($doSort) {
            sort($result);
        }

        return $result;
    }

    /**
     * Open a file without format the content
     *
     * @param string $file File with path
     * @return string Content of the file or error
     */
    public function openFileNoExplode($file) {
        $f = new SplFileInfo($file);
        $result = '';
        if ($f->isFile()) {
            $openFile = $f->openFile('r');
            $result = $openFile->fgets();
        } else {
            $result = '<p class="error">Il file ' . $f->getBasename() . ' non esiste</p>';
        }
        return $result;
    }

    /**
     * Delete a file
     *
     * @param string $file File with path
     * @return string Reuslt of operation
     */
    public function deleteFile($file) {
        $f = new SplFileInfo($file);
        if ($f->isFile()) {
            if (unlink($f)) {
                return '<h3>File cancellato</h3>';
            }
        }
    }

    /**
     * Get all files in a directory
     *
     * @param string $directory Directory to get files
     * @return array Array of files with attributes
     */
    public function getFiles($directory) {
        if (is_dir($directory)) {
            $iterator = new DirectoryIterator($directory);
            $fileArray = array();
            foreach ($iterator as $dir) {
                if ($dir->isFile()) {
                    $fileArray[] = array(
                        'nome' => $dir->getFilename(),
                        'data' => date('d-m-Y', $dir->getMTime()),
                        'size' => number_format(($dir->getSize() / 1048576), 0, ',', '.') . ' MB',
                        'ext' => pathinfo($dir->getFilename(), PATHINFO_EXTENSION)
                    );
                }
            }
        }
        sort($fileArray);
        return $fileArray;
    }

    /**
     * List only directories
     *
     * @param $directory
     * @return array
     * @since 1.8
     */
    public function getDirs($directory) {
        if (is_dir($directory)) {
            $iterator = new DirectoryIterator($directory);
            $fileArray = array();
            foreach ($iterator as $dir) {
                if ($dir->isDir() && $dir->getFilename() != '.' && $dir->getFilename() != '..') {
                    $fileArray[] = array(
                        'nome' => $dir->getFilename(),
                        'data' => date('d-m-Y', $dir->getMTime())
                    );
                }
            }
        }
        sort($fileArray);
        return $fileArray;
    }

    /**
     * Get all files in a directory recursively
     *
     * @param string $directory Directory to get files
     * @return array Array of files with attributes
     */
    public function getFilesRecursive($directory) {
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory), RecursiveIteratorIterator::SELF_FIRST);
        $fileArray = array();
        foreach ($objects as $name => $object) {
            if (!$object->isDir() && $object->getFilename() !== '.' && $object->getFilename() !== '..') {
                $fileArray[] = array(
                    'nome' => $object->getFilename(),
                    'data' => date('d-m-Y', $dir->getMTime()),
                    'size' => number_format(($dir->getSize() / 1048576), 0, ',', '.') . ' MB',
                    'ext' => pathinfo($object->getFilename(), PATHINFO_EXTENSION),
                    'path' => $object->getPathname()
                );
            }
        }
        sort($fileArray);
        return $fileArray;
    }

    /**
     * Create a directory
     *
     * @param type $directory Full path
     * @return void
     */
    public function createDir($directory) {
        $dir = new SplFileInfo($directory);
        if (!$dir->isDir()) {
            mkdir($dir);
        }
    }

    /**
     * Get extension of a file
     *
     * @param string $file File with path
     * @return string Exension of ile
     */
    public function getExtension($file) {
        return pathinfo($file['extension']);
    }

}
