<?php
class DocBlox_Reflection_DocBlock implements Reflector
{
  /** @var string */
  protected $short_description = '';

  /** @var DocBlox_Reflection_DocBlock_LongDescription */
  protected $long_description = null;

  /** @var DocBlox_Reflection_DocBlock_Tags[] */
  protected $tags = array();

  /**
   * Parses the given docblock and populates the member fields.
   *
   * @throws DocBlox_Reflection_Exception
   *
   * @param string|Reflector $docblock A docblock comment (including asterisks) or reflector supporting the
   *   getDocComment method.
   *
   * @return void
   */
  public function __construct($docblock)
  {
    if (is_object($docblock))
    {
      if (!method_exists($docblock, 'getDocComment'))
      {
        throw new DocBlox_Reflection_Exception(
          'Invalid object passed; the given reflector must support the getDocComment method'
        );
      }

      $docblock = $docblock->getDocComment();
    }

    $docblock = $this->cleanInput($docblock);

    list($short, $long, $tags) = $this->splitDocBlock($docblock);
    $this->short_description = $short;
    $this->long_description = new DocBlox_Reflection_DocBlock_LongDescription($long);
    $this->parseTags($tags);
  }

  /**
   * Strips the asterisks from the DocBlock comment.
   *
   * @param string $comment
   *
   * @return string
   */
  protected function cleanInput($comment)
  {
    $comment = trim(preg_replace('#[ \t]*(?:\/\*\*|\*\/|\*)?[ ]{0,1}(.*)?#', '$1', $comment));

    // reg ex above is not able to remove */ from a single line docblock
    if (substr($comment, -2) == '*/')
    {
      $comment = trim(substr($comment, 0, -2));
    }

    // normalize strings
    $comment = str_replace(array("\r\n", "\r"), "\n", $comment);

    return $comment;
  }


  /**
   * Splits the DocBlock into a short description, long description and block of tags.
   *
   * @param string $comment
   *
   * @author RichardJ Special thanks to RichardJ for the regex responsible for the split
   *
   * @return string[] containing the short-, long description and an element containing the tags.
   */
  protected function splitDocBlock($comment)
  {
    if (strpos($comment, '@') === 0)
    {
      $matches = array('', '', $comment);
    }
    else
    {
      /*
       * Splits the docblock into a short description, long description and tags section
       * - The short description is started from the first character until a dot is encountered followed by a whitespace OR
       *   two consecutive newlines (horizontal whitespace is taken into account to consider spacing errors)
       * - The long description, any character until a new line is encountered followed by an @ and word
       *   characters (a tag). This is optional.
       * - Tags; the remaining characters
       *
       * Big thanks to RichardJ for contributing this Regular Expression
       */
      preg_match('/(?x)
        \A (
          [^\n]+
          (?:
            (?! (?<=\.) \n | \n{2} )
            \n [^\n]+
          )*
          \.?
        )
        (?:
          \s*
          (?! @[a-zA-Z] )
          (
            [^\n]+
            (?: \n+
              (?! [ \t]* @[a-zA-Z] )
              [^\n]+
            )*
          )
        )?
        (\s+ [\s\S]*)?/usm', $comment, $matches
      );
      array_shift($matches);
    }

    while (count($matches) < 3)
    {
      $matches[] = '';
    }
    return $matches;
  }

  /**
   * Creates the tag objects.
   *
   * @param string $tags
   *
   * @return void
   */
  protected function parseTags($tags)
  {
    $result = array();
    foreach(explode("\n", $tags) as $tag_line)
    {
      if (trim($tag_line) === '')
      {
        continue;
      }

      if (isset($tag_line{0}) && ($tag_line{0} === '@'))
      {
        $result[] = $tag_line;
      }
      else
      {
        $result[count($result)-1] .= PHP_EOL . $tag_line;
      }
    }

    // create proper Tag objects
    foreach($result as $key => $tag_line)
    {
      $result[$key] = DocBlox_Reflection_DocBlock_Tag::createInstance($tag_line);
    }

    $this->tags = $result;
  }

  /**
   * Returns the opening line or also known as short description.
   *
   * @return string
   */
  public function getShortDescription()
  {
    return $this->short_description;
  }

  /**
   * Returns the full description or also known as long description.
   *
   * @return DocBlox_Reflection_DocBlock_LongDescription
   */
  public function getLongDescription()
  {
    return $this->long_description;
  }

  /**
   * Returns the tags for this DocBlock.
   *
   * @return DocBlox_Reflection_DocBlock_Tags[]
   */
  public function getTags()
  {
    return $this->tags;
  }

  /**
   * Checks if a tag of a certain type is present in this DocBlock.
   *
   * @param string $name
   *
   * @return bool
   */
  public function hasTag($name)
  {
    /** @var DocBlox_Reflection_DocBlock_Tag $tag */
    foreach($this->getTags() as $tag)
    {
      if ($tag->getName() == $name)
      {
        return true;
      }
    }

    return false;
  }

  static public function export()
  {

  }

  public function __toString()
  {

  }
}