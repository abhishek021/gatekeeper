<?php

namespace Psecio\Gatekeeper\Provider\Modler\Model;

use \Psecio\Gatekeeper\Provider\Modler\Model\Throttle as ThrottleModel;

class User extends \Psecio\Gatekeeper\Model\Mysql implements \Psecio\Gatekeeper\User\Model\ProviderInterface
{
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Database table name
     * @var string
     */
    protected $tableName = 'users';

    /**
     * Model properties
     * @var array
     */
    protected $properties = array(
        'username' => array(
            'description' => 'Username',
            'column' => 'username',
            'type' => 'varchar'
        ),
        'password' => array(
            'description' => 'Password',
            'column' => 'password',
            'type' => 'varchar'
        ),
        'email' => array(
            'description' => 'Email Address',
            'column' => 'email',
            'type' => 'varchar'
        ),
        'firstName' => array(
            'description' => 'First Name',
            'column' => 'first_name',
            'type' => 'varchar'
        ),
        'lastName' => array(
            'description' => 'Last Name',
            'column' => 'last_name',
            'type' => 'varchar'
        ),
        'created' => array(
            'description' => 'Date Created',
            'column' => 'created',
            'type' => 'datetime'
        ),
        'updated' => array(
            'description' => 'Date Updated',
            'column' => 'updated',
            'type' => 'datetime'
        ),
        'status' => array(
            'description' => 'Status',
            'column' => 'status',
            'type' => 'varchar'
        ),
        'id' => array(
            'description' => 'User ID',
            'column' => 'id',
            'type' => 'integer'
        ),
        'resetCode' => array(
            'description' => 'Password Reset Code',
            'column' => 'password_reset_code',
            'type' => 'varchar'
        ),
        'resetCodeTimeout' => array(
            'description' => 'Password Reset Code Timeout',
            'column' => 'password_reset_code_timeout',
            'type' => 'datetime'
        ),
        'groups' => array(
            'description' => 'Groups the User Belongs to',
            'type' => 'relation',
            'relation' => array(
                'model' => '\\Psecio\\Gatekeeper\\Provider\\Modler\\Collection\\UserGroup',
                'method' => 'findByUserId',
                'local' => 'id'
            )
        ),
        'permissions' => array(
            'description' => 'Permissions the user has',
            'type' => 'relation',
            'relation' => array(
                'model' => '\\Psecio\\Gatekeeper\\UserPermissionCollection',
                'method' => 'findByUserId',
                'local' => 'id'
            )
        ),
        'loginAttempts' => array(
            'description' => 'Number of login attempts by user',
            'type' => 'relation',
            'relation' => array(
                'model' => '\\Psecio\\Gatekeeper\\Provider\\Modler\\User',
                'method' => 'findAttemptsByUser',
                'local' => 'id',
                'return' => 'value'
            )
        ),
        'throttle' => array(
            'description' => 'Full throttle information for a user',
            'type' => 'relation',
            'relation' => array(
                'model' => '\\Psecio\\Gatekeeper\\Provider\\Modler\\Model\\Throttle',
                'method' => 'findByUserId',
                'local' => 'id'
            )
        ),
        'securityQuestions' => array(
            'description' => 'Security questions for the user',
            'type' => 'relation',
            'relation' => array(
                'model' => '\\Psecio\\Gatekeeper\\SecurityQuestionCollection',
                'method' => 'findByUserId',
                'local' => 'id'
            )
        )
    );

    /**
     * Check to see if the password needs to be rehashed
     *
     * @param string $value Password string
     * @return string Updated string value
     */
    public function prePassword($value)
    {
        if (password_needs_rehash($value, PASSWORD_DEFAULT) === true) {
            $value = password_hash($value, PASSWORD_DEFAULT);
        }
        return $value;
    }

    /**
     * Find the user by username
     *     If found, user is automatically loaded into model instance
     *
     * @param string $username Username
     * @return boolean Success/fail of find operation
     */
    public function findByUsername($username)
    {
        return $this->getDb()->find(
            $this, array('username' => $username)
        );
    }

    /**
     * Find a user by their given ID
     *
     * @param integer $userId User ID
     * @return boolean Success/fail of find operation
     */
    public function findByUserId($userId)
    {
        return $this->getDb()->find(
            $this, array('id' => $userId)
        );
    }

    /**
     * Attach a permission to a user account
     *
     * @param integer|PermissionModel $perm Permission ID or model isntance
     */
    public function addPermission($perm)
    {
        if ($perm instanceof PermissionModel) {
            $perm = $perm->id;
        }
        $perm = new UserPermissionModel($this->getDb(), array(
            'user_id' => $this->id,
            'permission_id' => $perm
        ));
        return $this->getDb()->save($perm);
    }

    /**
     * Revoke a user permission
     *
     * @param integer|PermissionModel $perm Permission ID or model instance
     * @return boolean Success/fail of delete
     */
    public function revokePermission($perm)
    {
        if ($perm instanceof PermissionModel) {
            $perm = $perm->id;
        }
        $perm = new UserPermissionModel($this->getDb(), array(
            'user_id' => $this->id,
            'permission_id' => $perm
        ));
        return $this->getDb()->delete($perm);
    }

    /**
     * Add a group to the user
     *
     * @param integer|GroupModel $group Add the user to a group
     * @return boolean Success/fail of add
     */
    public function addGroup($group)
    {
        if ($group instanceof GroupModel) {
            $group = $group->id;
        }
        $group = new UserGroupModel($this->getDb(), array(
            'group_id' => $group,
            'user_id' => $this->id
        ));
        return $this->getDb()->save($group);
    }

    /**
     * Revoke access to a group for a user
     *
     * @param integer|GroupModel $group ID or model of group to remove
     * @return boolean
     */
    public function revokeGroup($group)
    {
        if ($group instanceof GroupModel) {
            $group = $group->id;
        }
        $group = new UserGroupModel($this->getDb(), array(
            'group_id' => $group,
            'user_id' => $this->id
        ));
        return $this->getDb()->delete($group);
    }

    /**
     * Activate the user (status)
     *
     * @return boolean Success/fail of activation
     */
    public function activate()
    {
        // Verify we have a user
        if ($this->id === null) {
            return false;
        }
        $this->status = self::STATUS_ACTIVE;
        return $this->getDb()->save($this);
    }

    /**
     * Deactivate the user
     *
     * @return boolean Success/fail of deactivation
     */
    public function deactivate()
    {
        // Verify we have a user
        if ($this->id === null) {
            return false;
        }
        $this->status = self::STATUS_INACTIVE;
        return $this->getDb()->save($this);
    }

    /**
     * Generate and return the code for a password reset
     *     Also updates the user record
     *
     * @param integer $length Length of returned string
     * @return string Geenrated code
     */
    public function getResetPasswordCode($length = 80)
    {
        // Verify we have a user
        if ($this->id === null) {
            return false;
        }
        // Generate a random-ish code and save it to the user record
        $code = substr(bin2hex(openssl_random_pseudo_bytes($length)), 0, $length);
        $this->resetCode = $code;
        $this->resetCodeTimeout = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $this->getDb()->save($this);

        return $code;
    }

    /**
     * Check the given code against he value in the database
     *
     * @param string $resetCode Reset code to verify
     * @return boolean Pass/fail of verification
     */
    public function checkResetPasswordCode($resetCode)
    {
        // Verify we have a user
        if ($this->id === null) {
            return false;
        }
        if ($this->resetCode === null) {
            throw new Exception\PasswordResetInvalid('No reset code defined for user '.$this->username);
        }

        // Verify the timeout
        $timeout = new \DateTime($this->resetCodeTimeout);
        if ($timeout <= new \DateTime()) {
            $this->clearPasswordResetCode();
            throw new Exception\PasswordResetTimeout('Reset code has timeed out!');
        }

        // We made it this far, compare the hashes
        $result = (Gatekeeper::hash_equals($this->resetCode, $resetCode));
        if ($result === true) {
            $this->clearPasswordResetCode();
        }
        return $result;
    }

    /**
     * Clear all data from the passsword reset code handling
     * @return [type] [description]
     */
    public function clearPasswordResetCode()
    {
        // Verify we have a user
        if ($this->id === null) {
            return false;
        }
        $this->resetCode = null;
        $this->resetCodeTimeout = null;
        return $this->getDb()->save($this);
    }

    /**
     * Check to see if the user is in the group
     *
     * @param integer $groupId Group ID
     * @return boolean Found/not found in the group
     */
    public function inGroup($groupId)
    {
        $userGroup = new UserGroupModel($this->getDb());
        $userGroup = $this->getDb()->find($userGroup, array(
            'group_id' => $groupId,
            'user_id' => $this->id
        ));
        if ($userGroup->id === null) {
            return false;
        }
        return ($userGroup->id !== null && $userGroup->groupId === $groupId) ? true : false;
    }

    /**
     * Check to see if a user has a permission
     *
     * @param integer $permId Permission ID
     * @return boolean Found/not found in user permission set
     */
    public function hasPermission($permId)
    {
        $perm = new UserPermissionModel($this->getDb());
        $perm = $this->getDb()->find($perm, array(
            'permission_id' => $permId,
            'user_id' => $this->id
        ));
        return ($perm->id !== null && $perm->id === $permId) ? true : false;
    }

    /**
     * Check to see if a user is banned
     *
     * @return boolean User is/is not banned
     */
    public function isBanned()
    {
        $throttle = new ThrottleModel($this->getDb());
        $throttle = $this->getDb()->find($throttle, array('user_id' => $this->id));

        return ($throttle->status === ThrottleModel::STATUS_BLOCKED) ? true : false;
    }

    /**
     * Find the number of login attempts for a user
     *
     * @param integer $userId User ID [optional]
     * @return integer Number of login attempts
     */
    public function findAttemptsByUser($userId = null)
    {
        $userId = ($userId === null) ? $this->id : $userId;

        $throttle = new ThrottleModel($this->getDb());
        $throttle = $this->getDb()->find($throttle, array('user_id' => $userId));

        return ($throttle->attempts === null) ? 0 : $throttle->attempts;
    }

    /**
     * Grant permissions and groups (multiple) at the same time
     *
     * @param array $config Configuration settings (permissions & groups)
     */
    public function grant(array $config)
    {
        $return = true;
        if (isset($config['permissions'])) {
            $result = $this->grantPermissions($config['permissions']);
            if ($result === false && $return === true) {
                $return = false;
            }
        }
        if (isset($config['groups'])) {
            $result = $this->grantGroups($config['groups']);
            if ($result === false && $return === true) {
                $return = false;
            }
        }
        return $return;
    }

    /**
     * Handle granting of multiple permissions
     *
     * @param array $permissions Set of permissions (either IDs or objects)
     * @return boolean Success/fail of all saves
     */
    public function grantPermissions(array $permissions)
    {
        $return = true;
        foreach ($permissions as $permission) {
            $permission = ($permission instanceof PermissionModel) ? $permission->id : $permission;
            $userPerm = new UserPermissionModel($this->getDb(), array(
                'userId' => $this->id,
                'permissionId' => $permission
            ));
            $result = $this->getDb()->save($userPerm);
            if ($result === false && $return === true) {
                $return = false;
            }
        }
        return $return;
    }

    /**
     * Handle granting of multiple groups
     *
     * @param array $groups Set of groups (either IDs or objects)
     * @return boolean Success/fail of all saves
     */
    public function grantGroups(array $groups)
    {
        $return = true;
        foreach ($groups as $group) {
            $group = ($group instanceof GroupModel) ? $group->id : $group;
            $userGroup = new UserGroupModel($this->getDb(), array(
                'userId' => $this->id,
                'groupId' => $group
            ));
            $result = $this->getDb()->save($userGroup);
            if ($result === false && $return === true) {
                $return = false;
            }
        }
        return $return;
    }

    /**
     * Add a new security question to the current user
     *
     * @param array $data Security question data
     * @return boolean Result of save operation
     */
    public function addSecurityQuestion(array $data)
    {
        if (!isset($data['question']) || !isset($data['answer'])) {
            throw new \InvalidArgumentException('Invalid question/answer data provided.');
        }

        // Ensure that the answer isn't the same as the user's password
        if (password_verify($data['answer'], $this->password) === true) {
            throw new \InvalidArgumentException('Security question answer cannot be the same as password.');
        }

        $question = new SecurityQuestionModel($this->getDb(), array(
            'question' => $data['question'],
            'answer' => $data['answer'],
            'userId' => $this->id
        ));
        return $this->getDb()->save($question);
    }
}