<?php

namespace Eckinox\Controller\Application;

use Symfony\{
    Component\HttpFoundation\Request,
    Component\Routing\Annotation\Route,
    Component\Finder\Finder
};

use App\{
    Form\Application\UserType
};

use Eckinox\{
    Entity,
    Library\General\Arrays,
    Library\Symfony\Controller,
    Library\Symfony\Annotation\Security
};

class DataController extends Controller
{
    use \Eckinox\Library\Application\log;

    /**
     * @Route("/software/data", name="index_software_data")
     * @Security(privilege="SOFTWARE_UPDATE")
     */
    public function index(Request $request)
    {
        $list = [];

        $finder = new Finder();
        $finder->files()->in( $this->_get_data_dir() );

        foreach($finder as $find) {
            $custom_file = $this->_get_user_data_dir().$find->getFilename();
            $list[] = [
                'fullpath' => $find->getRealPath(),
                'filename' => $find->getFilename(),
                'last_mod' => file_exists($custom_file) ? filemtime($custom_file) : false /*$find->getRealPath())*/
            ];
        }

        return $this->render('@Eckinox/application/data/index.html.twig', [
            'list' => $list
        ]);
    }

    /**
     * @Route("/software/data/edit", name="edit_software_data")
     * @Security(privilege="SOFTWARE_UPDATE")
     */
    public function edit(Request $request)
    {
        $filename = $request->query->get('file');

        switch( $request->request->get('action') ) {
            case "save":
                $this->_save_json_file($filename, $request->request->get('data'));
                break;
        }

        $content = $this->_get_json_content($filename);

        return $this->render('@Eckinox/application/data/edit.html.twig', [
             # We could take the output from _save_json_file, but we're making sure shown data
             # is really the one fetched from the actual file.
            'data' => $content,
            'meta' => $this->_get_meta_func( explode('.json', $filename)[0] ),
            'filename' => $filename,
            'readonly' => false,
            'writeable' => is_writable( $this->_get_user_data_dir() )
        ]);
    }

    /**
     * @Route("/software/data/view", name="view_json")
     * @Security(privilege="SOFTWARE_UPDATE")
     */
    public function view(Request $request, $data = [], $filename = "")
    {
        return $this->render('@Eckinox/application/data/edit.html.twig', [
             # We could take the output from _save_json_file, but we're making sure shown data
             # is really the one fetched from the actual file.
            'data' => $data ?: $this->_get_json_content( $filename = $filename ?: $request->query->get('file') ),
            'filename' => $filename ?: "...",
            'readonly' => true,
            'writeable' => false
        ]);
    }

    /**
     * @Route("/software/data/logs/{id}", name="logs_json")
     * @Security(privilege="SOFTWARE_UPDATE")
     */
    public function logs(Request $request, $id = 0) {
        $log = $this->getDoctrine()
            ->getRepository( Entity\Application\Log::class )
            ->find($id);

        return $this->view($request, $log->getData(), "Données du journal d'activité #$id");
    }

    protected function _get_data_dir() {
        return $this->getParameter('app.data.path');
    }

    protected function _get_user_data_dir() {
        return $this->getParameter('app.data.path_custom');
    }

    protected function _save_json_file($filepath, $post_data) {
        $original = json_decode(file_get_contents($this->_get_data_dir().$filepath), true);
        $custom = Arrays::diff_assoc($this->_handle_json_data($post_data), $original);
        file_put_contents($this->_get_user_data_dir().$filepath, json_encode($custom, JSON_PRETTY_PRINT) );
    }

    protected function _get_json_content($filename) {
        return array_replace_recursive(
            json_decode(file_get_contents($this->_get_data_dir().$filename), true),
            file_exists($this->_get_user_data_dir().$filename) ? json_decode(file_get_contents($this->_get_user_data_dir().$filename), true) : []
        );
    }

    protected function _get_meta_func($module) {
        return new class($this, $module) {
            protected $ctrl = null;
            protected $module = "";

            public function __construct($ctrl, $module) {
                $this->module = $module;
                $this->ctrl = $ctrl;
            }

            public function input_type($value) {
                switch( gettype($value) ) {
                    case "int" :
                        return "numeric";

                    default:
                        return "text";
                }
            }

            // @TODO Whenever it's gonna be needed (for another version)
            public function get_type($parent_key, $key) {
            #    dump($this->ctrl->data("{$this->module}.$parent_key.@$key"));
            #    dump($parent_key, $key);
            }
        };
    }

    protected function _handle_json_data($data) {
        $retval = [];

        foreach($data as $key => $item) {
            if ( $key === "_value_" ) {
                return $item;
            }
            elseif ($key !== "_keyname_" ) {
                if ( $realkey = $item['_keyname_'] ?? $key) {
                    $retval[ $realkey ] = is_array($item) ? $this->_handle_json_data($item) : $item;
                }
            }
        }

        return $retval;
    }
}
