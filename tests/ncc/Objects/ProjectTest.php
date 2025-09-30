<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
     *
     *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
     *  associated documentation files (the "Software"), to deal in the Software without restriction, including without
     *  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
     *  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
     *  conditions:
     *
     *  The above copyright notice and this permission notice shall be included in all copies or substantial portions
     *  of the Software.
     *
     *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
     *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
     *  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
     *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
     *  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
     *  DEALINGS IN THE SOFTWARE.
     *
     */

    namespace ncc\Objects;

    use InvalidArgumentException;
    use ncc\Exceptions\InvalidPropertyException;
    use ncc\Objects\Project\Assembly;
    use ncc\Objects\Project\BuildConfiguration;
    use PHPUnit\Framework\TestCase;
    
    class ProjectTest extends TestCase
    {
        public function testConstructorWithDefaults(): void
        {
            $project = new Project([]);
            
            $this->assertEquals('src', $project->getSourcePath());
            $this->assertEquals('release', $project->getDefaultBuild());
            $this->assertNull($project->getEntryPoint());
            $this->assertNull($project->getUpdateSource());
            $this->assertNull($project->getRepository());
            $this->assertInstanceOf(Assembly::class, $project->getAssembly());
            $this->assertEmpty($project->getDependencies());
            $this->assertEmpty($project->getBuildConfigurations());
        }

        public function testConstructorWithFullData(): void
        {
            $data = [
                'source' => 'custom/src',
                'default_build' => 'debug',
                'entry_point' => 'main.php',
                'update_source' => 'org/package@repo',
                'repository' => [
                    'name' => 'test-repo',
                    'type' => 'github',
                    'host' => 'github.com',
                    'ssl' => true
                ],
                'assembly' => [
                    'name' => 'Test Project',
                    'package' => 'com.test.project',
                    'version' => '1.0.0'
                ],
                'dependencies' => [
                    'org/dep1@repo1',
                    'org/dep2=1.2.0@repo2'
                ],
                'build_configurations' => [
                    [
                        'name' => 'debug',
                        'output' => 'build/debug',
                        'type' => 'ncc'
                    ]
                ]
            ];

            $project = new Project($data);
            
            $this->assertEquals('custom/src', $project->getSourcePath());
            $this->assertEquals('debug', $project->getDefaultBuild());
            $this->assertEquals('main.php', $project->getEntryPoint());
            $this->assertInstanceOf(PackageSource::class, $project->getUpdateSource());
            $this->assertInstanceOf(RepositoryConfiguration::class, $project->getRepository());
            $this->assertInstanceOf(Assembly::class, $project->getAssembly());
            $this->assertCount(2, $project->getDependencies());
            $this->assertCount(1, $project->getBuildConfigurations());
        }

        public function testSourcePathGetterSetter(): void
        {
            $project = new Project([]);
            
            $project->setSourcePath('new/path');
            $this->assertEquals('new/path', $project->getSourcePath());
        }

        public function testSourcePathSetterEmpty(): void
        {
            $project = new Project([]);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The \'path\' parameter cannot be empty');
            $project->setSourcePath('');
        }

        public function testDefaultBuildGetterSetter(): void
        {
            $data = [
                'build_configurations' => [
                    [
                        'name' => 'custom',
                        'output' => 'out',
                        'type' => 'ncc'
                    ]
                ]
            ];
            $project = new Project($data);
            
            $project->setDefaultBuild('custom');
            $this->assertEquals('custom', $project->getDefaultBuild());
        }

        public function testDefaultBuildSetterEmpty(): void
        {
            $project = new Project([]);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The \'defaultBuild\' parameter cannot be empty');
            $project->setDefaultBuild('');
        }

        public function testDefaultBuildSetterNonExistent(): void
        {
            $project = new Project([]);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The build configuration \'nonexistent\' does not exist');
            $project->setDefaultBuild('nonexistent');
        }

        public function testEntryPointGetterSetter(): void
        {
            $project = new Project([]);
            
            $project->setEntryPoint('index.php');
            $this->assertEquals('index.php', $project->getEntryPoint());
            
            $project->setEntryPoint(null);
            $this->assertNull($project->getEntryPoint());
        }

        public function testUpdateSourceGetterSetter(): void
        {
            $project = new Project([]);
            
            $updateSource = new PackageSource('org/package@repo');
            $project->setUpdateSource($updateSource);
            $this->assertSame($updateSource, $project->getUpdateSource());
            
            $project->setUpdateSource(null);
            $this->assertNull($project->getUpdateSource());
        }

        public function testRepositoryGetterSetter(): void
        {
            $project = new Project([]);
            
            $repo = RepositoryConfiguration::fromArray([
                'name' => 'test',
                'type' => 'github',
                'host' => 'github.com',
                'ssl' => true
            ]);
            $project->setRepository($repo);
            $this->assertSame($repo, $project->getRepository());
            
            $project->setRepository(null);
            $this->assertNull($project->getRepository());
        }

        public function testAssemblyGetter(): void
        {
            $project = new Project([]);
            
            $this->assertInstanceOf(Assembly::class, $project->getAssembly());
        }

        public function testDependencyExists(): void
        {
            $data = [
                'dependencies' => ['org/package@repo']
            ];
            $project = new Project($data);
            
            $this->assertTrue($project->dependencyExists('org/package@repo'));
            $this->assertFalse($project->dependencyExists('nonexistent/package@repo'));
        }

        public function testAddDependencyString(): void
        {
            $project = new Project([]);
            
            $project->addDependency('org/package@repo');
            $this->assertTrue($project->dependencyExists('org/package@repo'));
            $this->assertCount(1, $project->getDependencies());
        }

        public function testAddDependencyPackageSource(): void
        {
            $project = new Project([]);
            
            $dependency = new PackageSource('org/package@repo');
            $project->addDependency($dependency);
            $this->assertTrue($project->dependencyExists('org/package@repo'));
            $this->assertCount(1, $project->getDependencies());
        }

        public function testAddDependencyAlreadyExists(): void
        {
            $data = [
                'dependencies' => ['org/package@repo']
            ];
            $project = new Project($data);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The dependency \'org/package@repo\' already exists');
            $project->addDependency('org/package@repo');
        }

        public function testRemoveDependency(): void
        {
            $data = [
                'dependencies' => ['org/package1@repo', 'org/package2@repo']
            ];
            $project = new Project($data);
            
            $project->removeDependency('org/package1@repo');
            $this->assertFalse($project->dependencyExists('org/package1@repo'));
            $this->assertTrue($project->dependencyExists('org/package2@repo'));
            $this->assertCount(1, $project->getDependencies());
        }

        public function testRemoveDependencyNotExists(): void
        {
            $project = new Project([]);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The dependency \'nonexistent/package@repo\' does not exist');
            $project->removeDependency('nonexistent/package@repo');
        }

        public function testSetDependencies(): void
        {
            $project = new Project([]);
            
            $dependencies = [
                new PackageSource('org/package1@repo'),
                new PackageSource('org/package2@repo')
            ];
            $project->setDependencies($dependencies);
            
            $this->assertCount(2, $project->getDependencies());
            $this->assertSame($dependencies, $project->getDependencies());
        }

        public function testGetBuildConfiguration(): void
        {
            $data = [
                'build_configurations' => [
                    [
                        'name' => 'debug',
                        'output' => 'out',
                        'type' => 'ncc'
                    ]
                ]  
            ];
            $project = new Project($data);
            
            $config = $project->getBuildConfiguration('debug');
            $this->assertInstanceOf(BuildConfiguration::class, $config);
            $this->assertEquals('debug', $config->getName());
            
            $this->assertNull($project->getBuildConfiguration('nonexistent'));
        }

        public function testBuildConfigurationExists(): void
        {
            $data = [
                'build_configurations' => [
                    [
                        'name' => 'debug',
                        'output' => 'out',
                        'type' => 'ncc'
                    ]
                ]
            ];
            $project = new Project($data);
            
            $this->assertTrue($project->buildConfigurationExists('debug'));
            $this->assertFalse($project->buildConfigurationExists('nonexistent'));
        }

        public function testSetBuildConfigurations(): void
        {
            $project = new Project([]);
            
            $configs = [
                new BuildConfiguration([
                    'name' => 'debug',
                    'output' => 'out',
                    'type' => 'ncc'
                ])
            ];
            $project->setBuildConfigurations($configs);
            
            $this->assertCount(1, $project->getBuildConfigurations());
            $this->assertSame($configs, $project->getBuildConfigurations());
        }

        public function testAddBuildConfiguration(): void
        {
            $project = new Project([]);
            
            $config = new BuildConfiguration([
                'name' => 'debug',
                'output' => 'out',
                'type' => 'ncc'
            ]);
            $project->addBuildConfiguration($config);
            
            $this->assertTrue($project->buildConfigurationExists('debug'));
            $this->assertCount(1, $project->getBuildConfigurations());
        }

        public function testAddBuildConfigurationAlreadyExists(): void
        {
            $data = [
                'build_configurations' => [
                    [
                        'name' => 'debug',
                        'output' => 'out',
                        'type' => 'ncc'
                    ]
                ]
            ];
            $project = new Project($data);
            
            $config = new BuildConfiguration([
                'name' => 'debug',
                'output' => 'out2',
                'type' => 'ncc'
            ]);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('A build configuration with the name \'debug\' already exists');
            $project->addBuildConfiguration($config);
        }

        public function testToArray(): void
        {
            $data = [
                'source' => 'custom/src',
                'default_build' => 'debug',
                'entry_point' => 'main.php',
                'update_source' => 'org/package@repo',
                'repository' => [
                    'name' => 'test-repo',
                    'type' => 'github',
                    'host' => 'github.com',
                    'ssl' => true
                ],
                'assembly' => [
                    'name' => 'Test Project',
                    'package' => 'com.test.project',
                    'version' => '1.0.0'
                ],
                'dependencies' => [
                    'org/dep1@repo1'
                ],
                'build_configurations' => [
                    [
                        'name' => 'debug',
                        'output' => 'build/debug',
                        'type' => 'ncc'
                    ]
                ]
            ];

            $project = new Project($data);
            $result = $project->toArray();
            
            $this->assertEquals('custom/src', $result['source']);
            $this->assertEquals('debug', $result['default_build']);
            $this->assertEquals('main.php', $result['entry_point']);
            $this->assertEquals('org/package@repo', $result['update_source']);
            $this->assertIsArray($result['repository']);
            $this->assertIsArray($result['assembly']);
            $this->assertIsArray($result['dependencies']);
            $this->assertIsArray($result['build_configurations']);
        }

        public function testFromArray(): void
        {
            $data = [
                'source' => 'custom/src',
                'default_build' => 'debug'
            ];

            $project = Project::fromArray($data);
            
            $this->assertInstanceOf(Project::class, $project);
            $this->assertEquals('custom/src', $project->getSourcePath());
            $this->assertEquals('debug', $project->getDefaultBuild());
        }

        public function testValidateArrayValid(): void
        {
            $data = [
                'source' => 'src',
                'default_build' => 'release',
                'entry_point' => 'main.php',
                'update_source' => 'org/package@repo',
                'repository' => [
                    'name' => 'test',
                    'type' => 'github',
                    'host' => 'github.com',
                    'ssl' => true
                ],
                'assembly' => [
                    'name' => 'Test',
                    'package' => 'com.test.app',
                    'version' => '1.0.0'
                ],
                'dependencies' => [
                    'org/dep@repo'
                ],
                'build_configurations' => [
                    [
                        'name' => 'release',
                        'output' => 'out',
                        'type' => 'ncc'
                    ]
                ]
            ];

            // Should not throw any exception
            Project::validateArray($data);
            $this->assertTrue(true);
        }

        public function testValidateArrayInvalidSource(): void
        {
            $data = ['source' => ''];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The project source path must be a non-empty string if set');
            Project::validateArray($data);
        }

        public function testValidateArrayInvalidDefaultBuild(): void
        {
            $data = ['default_build' => ''];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The default build configuration must be a non-empty string if set');
            Project::validateArray($data);
        }

        public function testValidateArrayInvalidEntryPoint(): void
        {
            $data = ['entry_point' => ''];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The entry point must be a non-empty string if set');
            Project::validateArray($data);
        }

        public function testValidateArrayInvalidUpdateSource(): void
        {
            $data = ['update_source' => 123];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The update source must be a string if set');
            Project::validateArray($data);
        }

        public function testValidateArrayInvalidRepository(): void
        {
            $data = ['repository' => 'not-array'];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The repository configuration must be an array if set');
            Project::validateArray($data);
        }

        public function testValidateArrayInvalidAssembly(): void
        {
            $data = ['assembly' => 'not-array'];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The assembly configuration must be an array if set');
            Project::validateArray($data);
        }

        public function testValidateArrayInvalidDependencies(): void
        {
            $data = ['dependencies' => 'not-array'];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The dependencies must be an array if set');
            Project::validateArray($data);
        }

        public function testValidateArrayInvalidDependencyItem(): void
        {
            $data = ['dependencies' => [123]];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Each dependency must be a string');
            Project::validateArray($data);
        }

        public function testValidateArrayInvalidBuildConfigurations(): void
        {
            $data = ['build_configurations' => 'not-array'];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The build configurations must be an array if set');
            Project::validateArray($data);
        }

        public function testValidateArrayInvalidBuildConfigurationItem(): void
        {
            $data = ['build_configurations' => ['not-array']];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Each build configuration must be an array');
            Project::validateArray($data);
        }
    }
