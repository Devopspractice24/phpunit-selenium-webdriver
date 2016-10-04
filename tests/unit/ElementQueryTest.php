<?php

namespace Sepehr\PHPUnitSelenium\Tests\Unit;

use Mockery;
use Facebook\WebDriver\WebDriverBy;
use Sepehr\PHPUnitSelenium\Exception\NoSuchElement;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Sepehr\PHPUnitSelenium\Util\Locator;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ElementQueryTest extends UnitSeleniumTestCase
{

    /** @test */
    public function findsElementsByAnInstanceOfWebDriverBy()
    {
        $this->injectMockedWebDriver(
            $this->webDriverMock
                ->shouldReceive('findElements')
                ->once()
                ->with(WebDriverBy::class)
                ->andReturn($expected = ['foo', 'bar', 'baz'])
                ->getMock()
        );

        $webDriverByMock = Mockery::mock(WebDriverBy::class);

        $this->assertSame($expected, $this->findBy($webDriverByMock));
    }

    /** @test */
    public function returnsAnEmptyArrayWhenNoElementIsFound()
    {
        $this->injectMockedWebDriver(
            $this->webDriverMock
                ->shouldReceive('findElements')
                ->once()
                ->with(WebDriverBy::class)
                ->andReturn($expected = [])
                ->getMock()
        );

        $webDriverByMock = Mockery::mock(WebDriverBy::class);

        $this->assertSame($expected, $this->findBy($webDriverByMock));
    }

    /** @test */
    public function unwrapsContainingArrayWhenFindsOnlyOneElement()
    {
        $this->injectMockedWebDriver(
            $this->webDriverMock
                ->shouldReceive('findElements')
                ->once()
                ->with(WebDriverBy::class)
                ->andReturn(['foo'])
                ->getMock()
        );

        $webDriverByMock = Mockery::mock(WebDriverBy::class);

        $this->assertSame('foo', $this->findBy($webDriverByMock));
    }

    /** @test */
    public function findsJustOneElement()
    {
        $this->injectMockedWebDriver(
            $this->webDriverMock
                ->shouldReceive('findElement')
                ->once()
                ->with(WebDriverBy::class)
                ->andReturn($expected = 'foo')
                ->getMock()
        );

        $webDriverByMock = Mockery::mock(WebDriverBy::class);

        $this->assertSame($expected, $this->findOneBy($webDriverByMock));
    }

    /** @test */
    public function throwsAnExceptionWhenTryingToFindJustOneElementAndItsNotThere()
    {
        $this->injectMockedWebDriver(
            $this->webDriverMock
                ->shouldReceive('findElement')
                ->once()
                ->with(WebDriverBy::class)
                ->andThrow(NoSuchElementException::class)
                ->getMock()
        );

        $this->expectException(NoSuchElement::class);

        $this->findOneBy(Mockery::mock(WebDriverBy::class));
    }

    /** @test */
    public function findsElementsByXpathLocator()
    {
        Mockery::mock('alias:' . Locator::class)
            ->shouldReceive('isXpath')
            ->once()
            ->with($locator = 'someLocator')
            ->andReturn(true);

        Mockery::mock('alias:' . WebDriverBy::class)
            ->shouldReceive('xpath')
            ->once()
            ->with($locator)
            ->andReturn(Mockery::self())
            ->mock();

        $this->injectMockedWebDriver(
            $this->webDriverMock
                ->shouldReceive('findElements')
                ->once()
                ->with(WebDriverBy::class)
                ->andReturn($expected = ['el1', 'el2'])
                ->getMock()
        );

        $this->assertSame($expected, $this->find($locator));
    }

    /** @test */
    public function throwsAnExceptionIfNoElementIsFoundByLocator()
    {
        Mockery::mock('alias:' . Locator::class)
            ->shouldReceive('isXpath')
            ->once()
            ->with($locator = 'someLocator')
            ->andReturn(false);

        Mockery::mock('alias:' . WebDriverBy::class)
            ->shouldReceive('xpath', 'cssSelector', 'name', 'id')
            ->withAnyArgs()
            ->andReturn(Mockery::self())
            ->mock();

        $this->injectMockedWebDriver(
            $this->webDriverMock
                ->shouldReceive('findElements')
                ->times(5)
                ->with(WebDriverBy::class)
                ->andReturn([])
                ->getMock()
        );

        $this->expectException(NoSuchElement::class);

        $this->find($locator);
    }

    /**
     * @test
     *
     * Tests default mechanisms of findBy*() methods.
     *
     * @param string $api Name of SeleniumTestCase API method.
     * @param array $args API method array of args.
     * @param string $mechanism WebDriverBy mechanism method to be expected to be called.
     * @param string|null $alt Parameter to be passed to specified WebDriverBy mechanism method, falls back to $args.
     *
     * @dataProvider elementQueryMethodProvider
     */
    public function findsElementsByDifferentMachanisms($api, array $args, $mechanism, $alt = null)
    {
        $this->injectMockedWebDriver(
            $this->webDriverMock
                ->shouldReceive('findElements')
                ->once()
                ->with(WebDriverBy::class)
                ->andReturn($expected = 'foundElement')
                ->getMock()
        );

        Mockery::mock('alias:' . WebDriverBy::class)
            ->shouldReceive($mechanism)
            ->once()
            ->with($alt ? $alt : $args[0])
            ->andReturn(Mockery::self())
            ->mock();

        $this->assertSame($expected, $this->$api(...$args));
    }

    /**
     * @test
     *
     * Tests fallback mechanism of findByBarOrBaz() methods.
     *
     * @param string $api Name of SeleniumTestCase API method.
     * @param array $args API method array of args.
     * @param array $mechanism WebDriverBy mechanism method to be expected to be called.
     * @param array $alt Parameter to be passed to specified WebDriverBy mechanism method, falls back to $args.
     *
     * @dataProvider elementQueryMethodWithFallbackProvider
     */
    public function findsElementsByDifferentFallbackMachanisms($api, array $args, array $mechanism, array $alt)
    {
        $this->injectMockedWebDriver(
            $this->webDriverMock
                ->shouldReceive('findElements')
                ->twice()
                ->with(WebDriverBy::class)
                ->andReturn([], $expected = 'foundElement')
                ->getMock()
        );

        Mockery::mock('alias:' . WebDriverBy::class)
            ->shouldReceive($mechanism[0])
            ->once()
            ->with($alt[0])
            ->andReturn(Mockery::self())
            ->shouldReceive($mechanism[1])
            ->once()
            ->with($alt[1])
            ->andReturn(Mockery::self())
            ->mock();

        $this->assertSame($expected, $this->$api(...$args));
    }

    /**
     * Element query data provider.
     *
     * We use this provider to test multiple find*() methods with one test.
     *
     * @return array
     */
    public static function elementQueryMethodProvider()
    {
        return [
            // Pattern:
            // [$api, $args[], $mechanism[, $alt]]
            ['findByName', ['elementName'], 'name'],
            ['findBySelector', ['ul > li .selector'], 'cssSelector'],
            ['findByClass', ['someClass'], 'cssSelector', '.someClass'],
            ['findById', ['someId'], 'id'],
            ['findByAttribute', ['data-dummy', 'someAttr', '*'], 'cssSelector', "*[data-dummy='someAttr']"],
            ['findByPartialAttribute', ['data-dummy', 'someAttr', '*'], 'cssSelector', "*[data-dummy*='someAttr']"],
            ['findByValue', ['someValue', '*'], 'cssSelector', "*[value='someValue']"],
            ['findByPartialValue', ['someValue', '*'], 'cssSelector', "*[value*='someValue']"],
            ['findByText', ['someText', '*'], 'xpath', "//*[text()='someText']"],
            ['findByBody', ['someText', '*'], 'xpath', "//*[text()='someText']"],
            ['findByPartialText', ['someText', '*'], 'xpath', "//*[contains(text(), 'someText')]"],
            ['findByTextOrValue', ['someText', '*'], 'xpath', "//*[text()='someText']"],
            ['findByPartialTextOrValue', ['someText', '*'], 'xpath', "//*[contains(text(), 'someText')]"],
            ['findByNameOrId', ['someName'], 'name'],
            ['findByLinkText', ['someText'], 'linkText'],
            ['findByLinkPartialText', ['someText'], 'partialLinkText'],
            ['findByLinkHref', ['https://g.cn/'], 'cssSelector', "a[href='https://g.cn/']"],
            ['findByLinkPartialHref', ['/sepehr'], 'cssSelector', "a[href*='/sepehr']"],
            ['findByXpath', ['/html/body/div/div[2]/div[1]/h1'], 'xpath'],
            ['findByTag', ['someTag'], 'tagName'],
            ['findByTabIndex', [7, '*'], 'cssSelector', "*[tabindex='7']"],
        ];
    }

    /**
     * Element query data provider.
     *
     * @return array
     */
    public static function elementQueryMethodWithFallbackProvider()
    {
        return [
            // Pattern:
            // [$api, $args[], $mechanism[], $alt[]]
            [
                'findByTextOrValue',
                ['someValue', '*'],
                ['xpath', 'cssSelector'],
                ["//*[text()='someValue']", "*[value='someValue']"]
            ],
            [
                'findByPartialTextOrValue',
                ['someValue', '*'],
                ['xpath', 'cssSelector'],
                ["//*[contains(text(), 'someValue')]", "*[value*='someValue']"]
            ],
            [
                'findByNameOrId',
                ['someId', '*'],
                ['name', 'id'],
                ['someId', 'someId']
            ],
        ];
    }
}
