<?php

namespace Drupal\Tests\testmate\Functional;

use Drupal\views\Views;

/**
 * Tests the user views.
 *
 * @group Testmate
 */
class UserViewsTest extends TestmateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user', 'views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_testmate_user'];

  /**
   * Test user view without caching.
   */
  public function testUserViewNoCache() {
    $this->createUsers();

    // Login to bypass page caching.
    $this->drupalLogin($this->drupalCreateUser(['access user profiles']));

    // Add test view to a list of views.
    $this->testmate->setUserViews('test_testmate_user');

    $this->drupalGet('/test-testmate-user');
    $this->drupalGet('/test-testmate-user');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->assertText('User 1');
    $this->assertText('User 2');
    $this->assertText('[TEST] User 3');

    $this->drupalGet('/test-testmate-user');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->testmate->enableTestMode();

    $this->drupalGet('/test-testmate-user');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->assertNoText('User 1');
    $this->assertNoText('User 2');
    $this->assertText('[TEST] User 3');

    $this->drupalGet('/test-testmate-user');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');
  }

  /**
   * Test user view with tag-based caching.
   */
  public function testUserViewCacheTag() {
    $this->createUsers();

    // Login to bypass page caching.
    $this->drupalLogin($this->drupalCreateUser(['access user profiles']));

    // Add test view to a list of views.
    $this->testmate->setUserViews('test_testmate_user');

    // Enable Tag caching for this view.
    $view = Views::getView('test_testmate_user');
    $view->setDisplay('page_1');
    $view->display_handler->overrideOption('cache', [
      'type' => 'tag',
    ]);
    $view->save();

    $this->drupalGet('/test-testmate-user');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'MISS');

    $this->assertText('User 1');
    $this->assertText('User 2');
    $this->assertText('[TEST] User 3');

    $this->drupalGet('/test-testmate-user');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'HIT');

    $this->testmate->enableTestMode();

    $this->drupalGet('/test-testmate-user');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'MISS');

    $this->assertNoText('User 1');
    $this->assertNoText('User 2');
    $this->assertText('[TEST] User 3');

    $this->drupalGet('/test-testmate-user');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'HIT');
  }

  /**
   * Test user view for default User page with tag-based caching.
   */
  public function testUserViewContentNoCache() {
    $this->createUsers();

    // Disable Tag caching for this view.
    $view = Views::getView('user_admin_people');
    $view->setDisplay('page_1');
    $view->display_handler->overrideOption('cache', [
      'type' => 'none',
    ]);
    $view->save();

    // Login to bypass page caching.
    $this->drupalLoginAdmin();

    $this->drupalGet('/admin/people');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->assertText('User 1');
    $this->assertText('User 2');
    $this->assertText('[TEST] User 3');

    $this->drupalGet('/admin/people');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->testmate->enableTestMode();

    $this->drupalGet('/admin/people');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->assertNoText('User 1');
    $this->assertNoText('User 2');
    $this->assertText('[TEST] User 3');

    $this->drupalGet('/admin/people');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');
  }

  /**
   * Helper to create users.
   */
  protected function createUsers() {
    for ($i = 0; $i < 2; $i++) {
      $name = sprintf('User %s %s', $i + 1, $this->randomMachineName());
      $email = str_replace(' ', '_', $name) . '@somedomain.com';
      $this->drupalCreateUser([], $name, FALSE, [
        'mail' => $email,
      ]);
    }

    $name = sprintf('[TEST] User %s %s', 3, $this->randomMachineName());
    $email = str_replace(' ', '_', $name) . '@example.com';
    $this->drupalCreateUser([], $name, FALSE, [
      'mail' => $email,
    ]);
  }

}