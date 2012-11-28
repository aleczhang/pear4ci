<?php
/**
 * phpDocumentor
 *
 * PHP Version 5
 *
 * @category  phpDocumentor
 * @package   Transformer
 * @author    Mike van Riel <mike.vanriel@naenius.com>
 * @copyright 2010-2011 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Transformer;

/**
 * Core class responsible for transforming the structure.xml file to a set of
 * artifacts.
 *
 * @category phpDocumentor
 * @package  Transformer
 * @author   Mike van Riel <mike.vanriel@naenius.com>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT
 * @link     http://phpdoc.org
 */
class Transformer extends TransformerAbstract
{
    /** @var string|null Target location where to output the artifacts */
    protected $target = null;

    /** @var \DOMDocument|null DOM of the structure as generated by the parser. */
    protected $source = null;

    /** @var \phpDocumentor\Transformer\Template[] */
    protected $templates = array();

    /** @var string */
    protected $templates_path = '';

    /** @var \phpDocumentor\Plugin\Core\Transformer\Behaviour\Collection */
    protected $behaviours = null;

    /** @var Transformation[] */
    protected $transformations = array();

    /** @var boolean */
    protected $parsePrivate = false;

    /**
     * Array containing prefix => URL values.
     *
     * What happens is that the transformer knows where to find external API
     * docs for classes with a certain prefix.
     *
     * For example: having a prefix HTML_QuickForm2_ will link an unidentified
     * class that starts with HTML_QuickForm2_ to a (defined) URL
     * i.e. http://pear.php.net/package/HTML_QuickForm2/docs/
     * latest/HTML_QuickForm2/${class}.html
     *
     * @var string
     */
    protected $external_class_docs = array();

    /**
     * Sets the path for the templates to the phpDocumentor default.
     */
    public function __construct()
    {
    }

    /**
     * Sets the target location where to output the artifacts.
     *
     * @param string $target The target location where to output the artifacts.
     *
     * @throws \InvalidArgumentException if the target is not a valid writable
     *   directory.
     *
     * @return void
     */
    public function setTarget($target)
    {
        $path = realpath($target);
        if (!file_exists($path) && !is_dir($path) && !is_writable($path)) {
            throw new \InvalidArgumentException(
                'Given target directory (' . $target . ') does not exist or '
                . 'is not writable'
            );
        }

        $this->target = $path;
    }

    /**
     * Returns the location where to store the artifacts.
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Sets the path where the templates are located.
     *
     * @param string $path Absolute path where the templates are.
     *
     * @return void
     */
    public function setTemplatesPath($path)
    {
        $this->templates_path = $path;
    }

    /**
     * Returns the path where the templates are located.
     *
     * @return string
     */
    public function getTemplatesPath()
    {
        return $this->templates_path;
    }

    /**
     * Sets the location of the structure file.
     *
     * @param string $source The location of the structure file as full path
     *  (may be relative).
     *
     * @throws \InvalidArgumentException if the source is not a valid readable
     *     file.
     *
     * @return void
     */
    public function setSource($source)
    {
        $source = trim($source);

        $xml = new \DOMDocument("1.0", "UTF-8");

        if (substr($source, 0, 5) === '<?xml') {
            $xml->loadXML($source);
        } else {
            $path = realpath($source);
            if (!file_exists($path) || !is_readable($path) || !is_file($path)) {
                throw new \InvalidArgumentException(
                    'Given source (' . $source . ') does not exist or is not '
                    . 'readable'
                );
            }

            // convert to dom document so that the writers do not need to
            $xml->load($path);
        }

        $this->source = $xml;
    }

    /**
     * Returns the source Structure.
     *
     * @return \DOMDocument|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Sets flag indicating whether private members and/or elements tagged
     * as {@internal} need to be displayed.
     *
     * @param bool $val True if all needs to be shown, false otherwise.
     *
     * @return void
     */
    public function setParseprivate($val)
    {
        $this->parsePrivate = (boolean)$val;
    }

    /**
     * Returns flag indicating whether private members and/or elements tagged
     * as {@internal} need to be displayed.
     *
     * @return bool
     */
    public function getParseprivate()
    {
        return $this->parsePrivate;
    }

    /**
     * Sets one or more templates as basis for the transformations.
     *
     * @param string|string[] $template Name or names of the templates.
     *
     * @return void
     */
    public function setTemplates($template)
    {
        $this->templates = array();

        if (!is_array($template)) {
            $template = array($template);
        }

        foreach ($template as $item) {
            $this->addTemplate($item);
        }
    }

    /**
     * Returns the list of templates which are going to be adopted.
     *
     * @return string[]
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Loads a template by name, if an additional array with details is
     * provided it will try to load parameters from it.
     *
     * @param string $name Name of the template to add.
     *
     * @return void
     */
    public function addTemplate($name)
    {
        // if the template is already loaded we do not reload it.
        if (isset($this->templates[$name])) {
            return;
        }

        $path = null;

        // if this is an absolute path; load the template into the configuration
        // Please note that this _could_ override an existing template when
        // you have a template in a subfolder with the same name as a default
        // template; we have left this in on purpose to allow people to override
        // templates should they choose to.
        $config_path = rtrim($name, DIRECTORY_SEPARATOR) . '/template.xml';
        if (file_exists($config_path) && is_readable($config_path)) {
            $path = rtrim($name, DIRECTORY_SEPARATOR);
            $template_name_part = basename($path);
            $cache_path = rtrim($this->getTemplatesPath(), '/\\')
            . DIRECTORY_SEPARATOR . $template_name_part;

            // move the files to a cache location and then change the path
            // variable to match the new location
            $this->copyRecursive($path, $cache_path);
            $path = $cache_path;

            // transform all directory separators to underscores and lowercase
            $name = strtolower(
                str_replace(
                    DIRECTORY_SEPARATOR,
                    '_',
                    rtrim($name, DIRECTORY_SEPARATOR)
                )
            );
        }

        // if we load a default template
        if ($path === null) {
            $path = rtrim($this->getTemplatesPath(), '/\\')
                    . DIRECTORY_SEPARATOR . $name;
        }

        if (!file_exists($path) || !is_readable($path)) {
            throw new \InvalidArgumentException(
                'The given template ' . $name.' could not be found or is not '
                . 'readable'
            );
        }

        // track templates to be able to refer to them later
        $this->templates[$name] = new Template($name, $path);
        $this->templates[$name]->populate(
            $this,
            file_get_contents($path  . DIRECTORY_SEPARATOR . 'template.xml')
        );
    }

    /**
     * Returns the transformation which this transformer will process.
     *
     * @return Transformation[]
     */
    public function getTransformations()
    {
        $result = array();
        foreach ($this->templates as $template) {
            foreach ($template as $transformation) {
                $result[] = $transformation;
            }
        }

        return $result;
    }

    /**
     * Executes each transformation.
     *
     * @return void
     */
    public function execute()
    {
        $source = $this->getSource();

        if (!$source) {
            throw new Exception(
                'Unable to process transformations; the source was not set '
                . 'correctly'
            );
        }

        // invoke pre-transform actions (i.e. enhance source file with additional
        // meta-data)
        \phpDocumentor\Event\Dispatcher::getInstance()->dispatch(
            'transformer.transform.pre',
            \phpDocumentor\Transformer\Event\PreTransformEvent
            ::createInstance($this)->setSource($source)
        );

        foreach ($this->getTransformations() as $transformation) {
            \phpDocumentor\Event\Dispatcher::getInstance()->dispatch(
                'transformer.transformation.pre',
                \phpDocumentor\Transformer\Event\PreTransformationEvent
                ::createInstance($this)->setSource($source)
            );

            $this->log(
                'Applying transformation'
                . ($transformation->getQuery()
                        ? (' query "' . $transformation->getQuery() . '"') : '')
                . ' using writer ' . get_class($transformation->getWriter())
                . ' on '.$transformation->getArtifact()
            );

            $transformation->execute($source);

            \phpDocumentor\Event\Dispatcher::getInstance()->dispatch(
                'transformer.transformation.post',
                \phpDocumentor\Transformer\Event\PostTransformationEvent
                ::createInstance($this)->setSource($source)
            );
        }

        \phpDocumentor\Event\Dispatcher::getInstance()->dispatch(
            'transformer.transform.post',
            \phpDocumentor\Transformer\Event\PostTransformEvent
            ::createInstance($this)->setSource($source)
        );
    }

    /**
     * Converts a source file name to the name used for generating the end result.
     *
     * This method strips down the given $name using the following rules:
     *
     * * if the $name is postfixed with .php then that is removed
     * * any occurance of \ or DIRECTORY_SEPARATOR is replaced with .
     * * any dots that the name starts or ends with is removed
     * * the result is postfixed with .html
     *
     * @param string $name Name to convert.
     *
     * @return string
     */
    public function generateFilename($name)
    {
        if (substr($name, -4) == '.php') {
            $name = substr($name, 0, -4);
        }

        return trim(str_replace(array(DIRECTORY_SEPARATOR, '\\'), '.', trim($name, DIRECTORY_SEPARATOR . '.')), '.')
            . '.html';
    }

    /**
     * Copies a file or folder recursively to another location.
     *
     * @param string $src The source location to copy
     * @param string $dst The destination location to copy to
     *
     * @throws \Exception if $src does not exist or $dst is not writable
     *
     * @return void
     */
    public function copyRecursive($src, $dst)
    {
        // if $src is a normal file we can do a regular copy action
        if (is_file($src)) {
            copy($src, $dst);
            return;
        }

        $dir = opendir($src);
        if (!$dir) {
            throw new \Exception('Unable to locate path "' . $src . '"');
        }

        // check if the folder exists, otherwise create it
        if ((!file_exists($dst)) && (false === mkdir($dst))) {
            throw new \Exception('Unable to create folder "' . $dst . '"');
        }

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->copyRecursive($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Adds a link to external documentation.
     *
     * Please note that the prefix string is matched against the
     * start of the class name and that the preceding \ for namespaces
     * should NOT be included.
     *
     * You can augment the URI with the name of the found class by inserting
     * the param {CLASS}. By default the class is inserted as-is; to insert a
     * lowercase variant use the parameter {LOWERCASE_CLASS}
     *
     * @param string $prefix Class prefix to match, i.e. Zend_Config_
     * @param string $uri    URI to link to when above prefix is encountered.
     *
     * @return void
     */
    public function setExternalClassDoc($prefix, $uri)
    {
        $this->external_class_docs[$prefix] = $uri;
    }

    /**
     * Sets a set of prefix -> url parts.
     *
     * @param string[] $external_class_docs Array containing prefix => URI pairs.
     *
     * @see self::setExternalClassDoc() for details on this feature.
     *
     * @return void
     */
    public function setExternalClassDocs($external_class_docs)
    {
        $this->external_class_docs = $external_class_docs;
    }

    /**
     * Returns the registered prefix -> url pairs.
     *
     * @return string[]
     */
    public function getExternalClassDocs()
    {
        return $this->external_class_docs;
    }

    /**
     * Retrieves the url for a given prefix.
     *
     * @param string $prefix Class prefix to retrieve a URL for.
     * @param string $class  If provided will replace the {CLASS} param with
     *  this string.
     *
     * @return string|null
     */
    public function getExternalClassDocumentLocation($prefix, $class = null)
    {
        if (!isset($this->external_class_docs[$prefix])) {
            return null;
        }

        $result = $this->external_class_docs[$prefix];
        if ($class !== null) {
            $result = str_replace(
                array('{CLASS}', '{LOWERCASE_CLASS}', '{UNPREFIXED_CLASS}'),
                array($class, strtolower($class), substr($class, strlen($prefix))),
                $result
            );
        }

        return $result;
    }

    /**
     * Returns the url for this class if it is registered.
     *
     * @param string $class FQCN to retrieve documentation URL for.
     *
     * @return null|string
     */
    public function findExternalClassDocumentLocation($class)
    {
        $class = ltrim($class, '\\');
        foreach (array_keys($this->external_class_docs) as $prefix) {
            if (strpos($class, $prefix) === 0) {
                return $this->getExternalClassDocumentLocation($prefix, $class);
            }
        }

        return null;
    }
}
