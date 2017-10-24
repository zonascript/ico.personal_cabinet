<?php
/**
 * Created by max
 * Date: 17/12/15
 * Time: 17:33
 */

namespace Modules\Core\Controllers;

class FrontendApiController extends ApiBaseController
{
    /**
     * Returns the access rules for this controller.
     * Override this method if you use the {@link filterAccessControl accessControl} filter.
     * @return array list of access rules. See {@link CAccessControlFilter} for details about rule specification.
     */
    public function accessRules()
    {
        return [
            [
                // deny all users
                'allow' => true,
                'users' => ['*'],
            ],
        ];
    }
}