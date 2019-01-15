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
            new TwigFilter('sortByField', array($this, 'sortByField')),

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

        if($twigFilter->needsEnvironment()) {
            $arguments = array_merge([$env], $arguments);
        }

        return call_user_func_array($twigFilter->getCallable(), $arguments);
    }
}
