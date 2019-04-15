<?php
namespace Eckinox\Library\Symfony\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Environment;
use Twig\Markup;
use Eckinox\Library\General\Git;
use Eckinox\Library\General\Data;

# This needs to be moved !
setlocale(LC_COLLATE, 'fr_CA.UTF-8');
setlocale(LC_TIME, 'fr_CA.UTF-8');

class Extension extends AbstractExtension
{
    use \Eckinox\Library\General\appData;

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFilters()
    {
        return array(
            /*
             * General
             */
            new TwigFilter('city', array($this, 'getCityName')),
            new TwigFilter('region', array($this, 'getRegionName')),
            new TwigFilter('borought', array($this, 'getBoroughtName')),
            new TwigFilter('data', array($this, 'getDataJson')),
            new TwigFilter('asort', array($this, 'asort')),
            new TwigFilter('icon', array($this, 'getIcon')),
            new TwigFilter('lcfirst', array($this, 'lcfirstFilter')),
            new TwigFilter('sortByField', array($this, 'sortByField')),
            new TwigFilter('yesNo', array($this, 'getYesNoFromBoolean')),
            new TwigFilter('withCurrentParams', array($this, 'addCurrentQueryParametersToUrl')),
            new TwigFilter('camelToSnakeCase', array('Eckinox\Library\General\StringEdit', 'camelToSnakeCase')),
            new TwigFilter('normalize', array('Eckinox\Library\General\StringEdit', 'normalize')),
            new TwigFilter('wbr', array('Eckinox\Library\General\StringEdit', 'wbr'), ['is_safe' => ['html']]),
            new TwigFilter('money', array('Eckinox\Library\General\StringEdit', 'formatMoney')),

            /*
             * Call filters dynamically
             */
            new TwigFilter('applyFilter', array($this, 'applyFilter'), [
                    'needs_environment' => true,
                ]
            ),
        );
    }


    public function getFunctions()
    {
        $git = "Eckinox\Library\General\Git";
        return [
            new TwigFunction("git_commit", [ $git, "getCommit" ]),
            new TwigFunction("git_branch", [ $git, "getBranch" ] ),
            new TwigFunction("git_commit_date", [ $git, "getCommitDate" ] ),
            new TwigFunction("get_translations_json", [ $this, "getTranslationsAsJson" ] ),
            new TwigFunction("data", [ $this, "getData" ] ),
            new TwigFunction("custom_field", [ $this, "generateCustomField" ] ),
            new TwigFunction("autocomplete", [ $this, "generateAutocompleteField" ] ),
            new TwigFunction("entity_dropdown", [ $this, "generateEntityDropdownField" ] ),
            new TwigFunction("uniqid", [ $this, "getUniqid" ] ),
        ];
    }

    public function asort($arr) {
        asort($arr, SORT_LOCALE_STRING);
        return $arr;
    }

    public function getRegionName($key) {
        return $this->data('localities.regions')[$key]['name'] ?? null;
    }

    public function getCityName($key) {
        return $this->data('localities.cities')[$key]['name'] ?? null;
    }

    public function getBoroughtName($key) {
        return $this->data('localities.boroughts')[$key]['name'] ?? null;
    }

    public function getDataJson($key) {
        return new Markup(json_encode($this->data($key), true), []);
    }

    public function getData($key) {
        return $this->data($key);
    }

    public function getIcon($key) {
        switch($key) {
            case "sent":
            case "accepted":
            case "active":
            case "in_progress":
                $value = '<i class="fas fa-check-circle green"></i>';
                break;
            case "refused":
            case "canceled":
            case "cancelled":
            case "deleted":
                $value = '<i class="fas fa-times-circle red"></i>';
                break;
            case "unsent":
            case "inactive":
                $value = '<i class="fas fa-check-circle grey"></i>';
                break;
            case "draft":
            case "pending":
                $value = '<i class="fas fa-exclamation-circle yellow"></i>';
                break;
            case "incomplete":
            case "unsent_error":
                $value = '<i class="fas fa-exclamation-circle red"></i>';
                break;
            case "closed":
                $value = '<i class="fas fa-check-circle grey"></i>';
                break;
            case "ready_to_disassemble":
                $value = '<i class="fas fa-wrench yellow"></i>';
                break;
            case "reserved":
                $value = '<i class="fas fa-lock-alt yellow"></i>';
                break;
            case "shipped":
                $value = '<i class="fas fa-truck blue"></i>';
                break;
            case "delivered":
                $value = '<i class="fas fa-box-check green"></i>';
                break;
            default:
                return $key;
        }

        return new Markup($value, []);
    }

    public function lcfirstFilter($string) {
        return lcfirst($string);
    }

    public function sortByField($array, $fieldKey = null, $direction = 'asc') {
        if (!is_array($array)) {
            return $array;
        }

        uasort($array, function($a, $b) use ($fieldKey, $direction) {
            $dataA = is_array($a) ? Data::array_get($a, $fieldKey) : null;
            $dataB = is_array($b) ? Data::array_get($b, $fieldKey) : null;

            if ($dataA == $dataB) {
                return 0;
            }

            $result = $dataA > $dataB ? 1 : -1;

            return strtolower($direction) == 'asc' ? $result : $result * -1;
        });

        return $array;
    }

    /*
     * Returns all of the available translation messages as JSON
     */
    public function getTranslationsAsJson() {
        $messages = $this->container->get('translator')->getCatalogue()->all();
        return new Markup(json_encode($messages, true), []);
    }

    public function generateCustomField($name, $field) {
        $field['name'] = $name;
        $field['type'] = $field['type'] ?? 'input';
        $field['choices'] = $field['choices'] ?? [];
        $field['attrs'] = $field['attrs'] ?? [];

        if ($entityClass = $field['entity'] ?? null) {
            $em = $this->container->get('doctrine')->getManager();
            $entity = new $entityClass();

            $queryEntityName = str_replace('Entity:', '', str_replace('\\', ':', $entityClass));
            $queryString = 'SELECT e FROM ' . $queryEntityName . ' e WHERE 1 = 1 ';

            # Don't load archived entities...
            if (property_exists($entity, 'isArchived')) {
                $queryString .= ' AND e.isArchived = false';
            }
            # Or deleted ones...
            if (property_exists($entity, 'isDeleted')) {
                $queryString .= ' AND e.isDeleted = false';
            }
            # Or deleted ones, using statuses...
            if (property_exists($entity, 'status')) {
                $queryString .= ' AND e.status != "deleted"';
            }

            $query = $em->createQuery($queryString);
            $query->useQueryCache(false);

            $entities = $query->getResult();

            foreach ($entities as $entity) {
                $field['choices'][$entity->getId()] = $entity->_toString();
            }
        }

        $input = 'input';
        if (in_array($field['type'], ['select', 'checkbox', 'radio'])) {
            $input = $field['type'];
        }

        $html = $this->container->get('twig')->render('@Eckinox/html/input/' . $input . '.html.twig', ['infos' => $field]);
        return new Markup($html, []);
    }

    public function generateAutocompleteField($settings) {
        if (isset($settings['entity'])) {
            $settings['entity'] = str_replace('\\', '\\\\', $settings['entity']);
        }
        $html = $this->container->get('twig')->render('@Eckinox/html/input/autocomplete.html.twig', ['settings' => $settings]);
        return new Markup($html, []);
    }

    public function generateEntityDropdownField($entityClass, $name, $labelProperty, $currentValue = null) {
        $entities = $this->container->get('doctrine')->getRepository($entityClass)->findAll();
        $choices = [];

        foreach ($entities as $entity) {
            $choices[$entity->getId()] = $entity->get($labelProperty);
        }

        $html = $this->container->get('twig')->render('@Eckinox/html/input/select.html.twig', [
            'infos' => [
                'name' => $name,
                'choices' => $choices,
                'value' => $currentValue ? $currentValue->getId() : null
            ]
        ]);

        return new Markup($html, []);
    }

    public function getUniqid() {
        return uniqid();
    }

    public function getYesNoFromBoolean($value) {
        return $this->container->get('translator')->trans($value ? 'yes' : 'no', [], 'application');
    }

    public function addCurrentQueryParametersToUrl($url) {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $parameters = $request->query->all();
        $path = array_shift($parameters);

        $queryString = http_build_query($parameters);

        if (strpos($url, '?') === false) {
            $queryString = '?' . $queryString;
        } else {
            $queryString = '&' . $queryString;
        }

        return $url . $queryString;
    }

    /*
     * Get app parameters
     */
    public function getParameter($param) {
        return $this->container->getParameter($param);
    }

    /*
     * Call filters dynamically
     */
    public function applyFilter(Environment $env, $value, $filterName, $arguments = [])
    {
        $twigFilter = $env->getFilter($filterName);
        $arguments = array_merge([$value], (array)$arguments);

        if (!$twigFilter) {
            return $value;
        }

        if ($twigFilter->needsEnvironment()) {
            $arguments = array_merge([$env], $arguments);
        }

        return call_user_func_array($twigFilter->getCallable(), $arguments);
    }
}
