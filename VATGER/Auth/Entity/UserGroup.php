<?php
namespace VATGER\Auth\Entity;

use XF\Api\Result\EntityResult;

class UserGroup extends XFCP_UserGroup
{
    /**
     * @param EntityResult $result
     * @param int $verbosity
     * @param array $options
     *
     * @api-desc Information about the user. Different information will be included based on permissions and verbosity.
     *
     * @api-out <perm> int $user_group_id
     * @api-out <perm> str $title
     * @api-out <perm> int $display_style_priority
     * @api-out <perm> str $username_css
     * @api-out <perm> str $user_title
     * @api-out <perm> str $banner_css_class
     * @api-out <perm> str $banner_text
 */
    protected function setupApiResultData(EntityResult $result, $verbosity = self::VERBOSITY_NORMAL, array $options = []
    )
    {

        if ($result->getResultType() === EntityResult::TYPE_API) {
            $result->includeColumn('user_group_id');
            $result->includeColumn('title');
            $result->includeColumn('display_style_priority');
            $result->includeColumn('username_css');
            $result->includeColumn('user_title');
            $result->includeColumn('banner_css_class');
            $result->includeColumn('banner_text');
        }
    }
}
