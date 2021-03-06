<?php
/**
 * phpDocumentor Return tag test.
 *
 * @author    Mike van Riel <mike.vanriel@naenius.com>
 * @copyright Copyright (c) 2010-2011 Mike van Riel / Naenius. (http://www.naenius.com)
 */

namespace phpDocumentor\Reflection\DocBlock\Tag;

/**
 * Test class for phpDocumentor_Reflection_DocBlock_ReturnTag.
 *
 * @author     Mike van Riel <mike.vanriel@naenius.com>
 * @copyright  Copyright (c) 2010-2011 Mike van Riel / Naenius. (http://www.naenius.com)
 */
class ReturnTagTest extends ParamTagTest
{
    /**
     * Test that the \phpDocumentor\Reflection\DocBlock\Tag\ReturnTag can
     * understand the Return DocBlock.
     *
     * @param string $content
     * @param string $extracted_type
     * @param string $extracted_description
     *
     * @covers \phpDocumentor\Reflection\DocBlock\Tag\ReturnTag::__construct
     *
     * @dataProvider provideDataForConstructor
     *
     * @return void
     */
    public function testConstructorParsesInputsIntoCorrectFields(
        $content, $extracted_type, $extracted_description
    ) {
        $tag = new ReturnTag('return', $content);

        $this->assertEquals($extracted_type,          $tag->getTypes());
        $this->assertEquals($extracted_description,   $tag->getDescription());
    }

    /**
     * Data provider for testConstructorParsesInputsIntoCorrectFields()
     *
     * @return array
     */
    public function provideDataForConstructor()
    {
        return array(
            array('', array(), ''),
            array('int', array('int'), ''),
            array('int Number of Bobs', array('int'), 'Number of Bobs'),
        );
    }
}
