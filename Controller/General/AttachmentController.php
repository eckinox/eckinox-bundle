<?php

namespace Eckinox\Controller\General;

use Eckinox\Library\Symfony\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class AttachmentController extends Controller
{
    /**
     * @Route("/attachments/tree", name="attachments_tree")
     */
    public function tree(Request $request) {
        $domain = $request->request->get('domain');
        $module = $request->request->get('module');
        $objectId = $request->request->get('objectId');
        $tmpFolder = $request->request->get('tmpFolder');
        $attachmentsPath = $this->getParameter('app.attachments.path');
        $modulePath = implode(DIRECTORY_SEPARATOR, $tmpFolder ? [ sys_get_temp_dir(), $tmpFolder ] : [$attachmentsPath.$domain,  $module, $objectId]);
        $tree = $this->data(implode('.', [$domain, $module, 'config.attachments.tree']));

        if(!$objectId && !$tmpFolder) {
            $result = [
                'result' => 'error',
                'message' => [
                    'error' => $this->trans(
                        'attachments.message.error.you_must_save',
                        [],
                        'general'
                    )
                ]
            ];

            $response = new Response();

            $response->setContent(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        if(!file_exists($modulePath)) { mkdir($modulePath, 0775 , true); }

        $this->setFoldersFiles($modulePath, $tree);

        return $this->render('@Eckinox/html/widget/attachments.html.twig', [
            "tree" => $tree,
            "domain" => $domain,
            "module" => $module,
            "objectId" => $objectId,
            "tmpFolder" => $tmpFolder,
        ]);
    }

    /**
     * @Route("/attachments/upload", name="attachments_upload")
     */
    public function uploadFile(Request $request) {
        $domain = $request->request->get('domain');
        $module = $request->request->get('module');
        $objectId = $request->request->get('object_id');
        $path = $request->request->get('path');
        $attachmentsPath = $this->getParameter('app.attachments.path');
        $tmpFolder = $request->request->get('tmp_folder');
        $currentPath = implode(DIRECTORY_SEPARATOR, $tmpFolder ? [ sys_get_temp_dir() , $tmpFolder, $path ] : [$attachmentsPath.$domain,  $module, $objectId, $path]);
        $file = $request->files->get('file');
        $fileName = $request->request->get('file_name');
        $result = [];

        if(!file_exists(implode(DIRECTORY_SEPARATOR, [$currentPath, $fileName]))) {
            $file->move(
                $currentPath,
                $fileName
            );

            $result = [
                'result' => 'success'
            ];
        } else {
            $result = [
                'result' => 'error',
                'message' => [
                    'error' => $this->trans(
                        'attachments.message.error.already_exists',
                        [],
                        'general'
                    )
                ]
            ];
        }

        $response = new Response();

        $response->setContent(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/attachments/{domain}/{module}/{objectId}/{path}/{fileName}/{action?}", name="attachments_open")
     */
    public function openFile(Request $request, $domain, $module, $objectId, $path, $fileName, $action = null) {
        $attachmentsPath = $this->getParameter('app.attachments.path');
        $currentPath = implode(DIRECTORY_SEPARATOR, [$attachmentsPath.$domain,  $module, $objectId, str_replace('-', '/', $path)]);
        $file = implode(DIRECTORY_SEPARATOR, [$currentPath, $fileName]);

        if (file_exists($file)) {

            switch($action) {
                case "delete":
                    unlink($file);

                    # Redirect to current page
                    return $this->redirect($request->server->get('HTTP_REFERER'));

                default:
                    return new BinaryFileResponse($file);

            }

        }

        return $this->renderText('Pièce jointe supprimé');
    }

    public static function setFoldersFiles($path, &$tree) {
        $total = 0;

        foreach($tree as &$folder) {
            $folder['files'] = [];
            $folderPath = implode(DIRECTORY_SEPARATOR, [$path, $folder['directory']]);

            if(!file_exists($folderPath)) { mkdir($folderPath, 0775 , true); }

            /*
             * Scan the folder to get its files
             */
            foreach(scandir($folderPath) as $file) {
                if(!is_dir(implode(DIRECTORY_SEPARATOR, [$folderPath, $file])) && !in_array($file, ['.', '..', '.gitkeep', '.gitignore'])) {
                    $folder['files'][] = $file;
                }
            }

            $count = ($folder['children'] ?? false) ? static::setFoldersFiles($folderPath, $folder['children']) : 0;
            $folder['total_files'] = $count + count($folder['files']);
            $total += $folder['total_files'];
        }

        return $total;
    }
}

