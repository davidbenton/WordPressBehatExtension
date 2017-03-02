<?php

namespace StephenHarris\WordPressBehatExtension\Context\Page;

class EditPostPage extends AdminPage
{

    protected $path = '/wp-admin/post.php?post={id}&action=edit';

    /**
     * @param array $urlParameters
     * @param string $postType
     *
     * @return Page
     */
    public function open(array $urlParameters = array(), $postType = null)
    {
        $url = $this->getUrl($urlParameters);

        $this->getDriver()->visit($url);

        $this->verify($urlParameters, $postType);

        return $this;
    }

    /**
     * @param array $urlParameters
     * @param string $postType
     */
    protected function verify(array $urlParameters, $postType = null)
    {
        $this->verifyResponse();
        $this->verifyUrl( $urlParameters );
        $this->verifyPage( $postType );
    }

    /**
     * @param string $postType
     */
    protected function verifyPage( $postType )
    {
        if ( is_null( $postType) ):
          $postType = 'post';
        endif;

        $post_type_object = get_post_type_object( $postType );
        $title = $post_type_object->labels->edit_item;
        $this->assertHasHeader( $title );
    }
}
