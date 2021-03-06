<?php

namespace SilverStripe\Security;

use SilverStripe\Core\Object;
use SilverStripe\ORM\ValidationResult;

/**
 * This class represents a validator for member passwords.
 *
 * <code>
 * $pwdVal = new PasswordValidator();
 * $pwdValidator->minLength(7);
 * $pwdValidator->checkHistoricalPasswords(6);
 * $pwdValidator->characterStrength(3, array("lowercase", "uppercase", "digits", "punctuation"));
 *
 * Member::set_password_validator($pwdValidator);
 * </code>
 */
class PasswordValidator extends Object
{

    private static $character_strength_tests = array(
        'lowercase' => '/[a-z]/',
        'uppercase' => '/[A-Z]/',
        'digits' => '/[0-9]/',
        'punctuation' => '/[^A-Za-z0-9]/',
    );

    protected $minLength, $minScore, $testNames, $historicalPasswordCount;

    /**
     * Minimum password length
     *
     * @param int $minLength
     * @return $this
     */
    public function minLength($minLength)
    {
        $this->minLength = $minLength;
        return $this;
    }

    /**
     * Check the character strength of the password.
     *
     * Eg: $this->characterStrength(3, array("lowercase", "uppercase", "digits", "punctuation"))
     *
     * @param int $minScore The minimum number of character tests that must pass
     * @param array $testNames The names of the tests to perform
     * @return $this
     */
    public function characterStrength($minScore, $testNames)
    {
        $this->minScore = $minScore;
        $this->testNames = $testNames;
        return $this;
    }

    /**
     * Check a number of previous passwords that the user has used, and don't let them change to that.
     *
     * @param int $count
     * @return $this
     */
    public function checkHistoricalPasswords($count)
    {
        $this->historicalPasswordCount = $count;
        return $this;
    }

    /**
     * @param String $password
     * @param Member $member
     * @return ValidationResult
     */
    public function validate($password, $member)
    {
        $valid = ValidationResult::create();

        if ($this->minLength) {
            if (strlen($password) < $this->minLength) {
                $valid->addError(
                    sprintf(
                        _t(
                            'SilverStripe\\Security\\PasswordValidator.TOOSHORT',
                            'Password is too short, it must be %s or more characters long'
                        ),
                        $this->minLength
                    ),
                    'bad',
                    'TOO_SHORT'
                );
            }
        }

        if ($this->minScore) {
            $score = 0;
            $missedTests = array();
            foreach ($this->testNames as $name) {
                if (preg_match(self::config()->character_strength_tests[$name], $password)) {
                    $score++;
                } else {
                    $missedTests[] = _t(
                        'SilverStripe\\Security\\PasswordValidator.STRENGTHTEST' . strtoupper($name),
                        $name,
                        'The user needs to add this to their password for more complexity'
                    );
                }
            }

            if ($score < $this->minScore) {
                $valid->addError(
                    sprintf(
                        _t(
                            'SilverStripe\\Security\\PasswordValidator.LOWCHARSTRENGTH',
                            'Please increase password strength by adding some of the following characters: %s'
                        ),
                        implode(', ', $missedTests)
                    ),
                    'bad',
                    'LOW_CHARACTER_STRENGTH'
                );
            }
        }

        if ($this->historicalPasswordCount) {
            $previousPasswords = MemberPassword::get()
                ->where(array('"MemberPassword"."MemberID"' => $member->ID))
                ->sort('"Created" DESC, "ID" DESC')
                ->limit($this->historicalPasswordCount);
            /** @var MemberPassword $previousPassword */
            foreach ($previousPasswords as $previousPassword) {
                if ($previousPassword->checkPassword($password)) {
                    $valid->addError(
                        _t(
                            'SilverStripe\\Security\\PasswordValidator.PREVPASSWORD',
                            'You\'ve already used that password in the past, please choose a new password'
                        ),
                        'bad',
                        'PREVIOUS_PASSWORD'
                    );
                    break;
                }
            }
        }

        return $valid;
    }
}
