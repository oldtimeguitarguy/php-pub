<?php

namespace spec\OldTimeGuitarGuy\Pub;

use Prophecy\Argument;
use PhpSpec\ObjectBehavior;
use OldTimeGuitarGuy\Pub\Pub;
use OldTimeGuitarGuy\Pub\Helpers\Pather;
use OldTimeGuitarGuy\Pub\Helpers\Globaler;
use OldTimeGuitarGuy\Pub\Exceptions\PathException;
use OldTimeGuitarGuy\Pub\Exceptions\PubReservedVariableException;

class PubSpec extends ObjectBehavior
{
	function let()
	{
		$this->beConstructedWith([], '/public');
	}

    function it_is_initializable()
    {
        $this->shouldHaveType(Pub::class);
    }

	function it_can_return_the_public_path()
	{
		$this->path()->shouldReturn('/public');
	}

	function it_can_return_a_path_within_the_public_path()
	{
		$this->path('bar.jpg')->shouldReturn('/public/bar.jpg');
		$this->path('/bar.jpg')->shouldReturn('/public/bar.jpg');
		$this->path('images/bar.jpg')->shouldReturn('/public/images/bar.jpg');
		$this->path('/images/bar.jpg')->shouldReturn('/public/images/bar.jpg');
		$this->path('/images/')->shouldReturn('/public/images');
	}

	function it_can_add_paths()
	{
		$this->addPath('tpl', 'assets/templates');
		$this->addPath('img', '/images/');
		$this->addPath(['fnt' => '/assets/front-end/fonts']);
		$this->addPath([
			'js' => 'builds/scripts/',
			'css' => '/builds/css'
		]);

		$this->path()->shouldReturn('/public');
		$this->tpl()->shouldReturn('/public/assets/templates');
		$this->tpl('test.html')->shouldReturn('/public/assets/templates/test.html');
		$this->img()->shouldReturn('/public/images');
		$this->img('test.jpg')->shouldReturn('/public/images/test.jpg');
		$this->fnt()->shouldReturn('/public/assets/front-end/fonts');
		$this->fnt('myfont.otf')->shouldReturn('/public/assets/front-end/fonts/myfont.otf');
		$this->js()->shouldReturn('/public/builds/scripts');
		$this->js('app.js')->shouldReturn('/public/builds/scripts/app.js');
		$this->css()->shouldReturn('/public/builds/css');
		$this->css('app.css')->shouldReturn('/public/builds/css/app.css');

		$this->shouldThrow(PathException::class)->duringPoop('testing');
	}

	function it_allows_base_overrides_when_prepended_with_at()
	{
		$this->addPath('at', '@http://testing.com');

		$this->at('foo/bar')->shouldReturn('http://testing.com/foo/bar');
	}

	function it_parses_variables_in_the_base_path()
	{
		// Add a new variable
		$this->addVariable('version', 42);
		
		// Expect exception thrown if we try to overwrite path
		$this->shouldThrow(PubReservedVariableException::class)
			->duringAddVariable('path', 'foo.jpg');

		// Create some paths using variables
		$this->addPath('var1', 'foo/bar/{{path}}.gz?{{timestamp}}');
		$this->addPath('var2', 'foo/bar/{{path}}-{{timestamp}}.js?{{version}}');
		$this->addPath('var3', 'foo/{{timestamp}}/bar/{{timestamp}}/{{version}}');
		$this->addPath('var4', '@http://google.com/{{version}}{{version}}{{path}}');

		// Test out those paths
		$time = time();
		$this->var1('test.js')->shouldReturn("/public/foo/bar/test.js.gz?$time");
		$this->var2('test.js')->shouldReturn("/public/foo/bar/test.js-$time.js?42");
		$this->var3('test.js')->shouldReturn("/public/foo/$time/bar/$time/42/test.js");
		$this->var4('test.js')->shouldReturn("http://google.com/4242test.js");
	}

	function it_can_create_global_pub_functions()
	{
		Globaler::removeInstance(Pub::class);

		Pub::globalize([
			'templates' => 'angular/templates',
			'images'    => 'static-assets/images',
			'scripts'   => 'builds/scripts'
		]);

		$this->assertSame('/angular/templates/test.html', templates('test.html'));
		$this->assertSame('/static-assets/images/test.jpg', images('test.jpg'));
		$this->assertSame('/builds/scripts/test.js', scripts('test.js'));
	}

	protected function assertSame($expected, $actual)
	{
		if ( strcmp($expected, $actual) !== 0 ) {
			throw new \Exception("Same assertion failed. Expected {$expected}, but got {$actual}");
		}

		return true;
	}
}
