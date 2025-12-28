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
            $this->assertNull($project->getDependencies());
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
                'execution_units' => [
                    [
                        'name' => 'main.php',
                        'entry' => 'main.php'
                    ]
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
            $this->assertIsArray($project->getDependencies());
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
            $this->expectExceptionMessage('The source path cannot be an empty string');
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
            $this->expectExceptionMessage('The default build name cannot be an empty string');
            $project->setDefaultBuild('');
        }

        public function testDefaultBuildSetterNonExistent(): void
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
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('The build configuration \'nonexistent\' does not exist');
            $project->setDefaultBuild('nonexistent');
        }

        public function testEntryPointGetterSetter(): void
        {
            $data = [
                'execution_units' => [
                    [
                        'name' => 'index.php',
                        'entry' => 'index.php'
                    ]
                ]
            ];
            $project = new Project($data);
            
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
            
            $this->assertTrue($project->dependencyExists('package'));
            $this->assertFalse($project->dependencyExists('nonexistent'));
        }

        public function testAddDependencyString(): void
        {
            $project = new Project([]);
            
            $project->addDependency('package', 'org/package@repo');
            $this->assertTrue($project->dependencyExists('package'));
            $this->assertIsArray($project->getDependencies());
            $this->assertCount(1, $project->getDependencies());
        }

        public function testAddDependencyPackageSource(): void
        {
            $project = new Project([]);
            
            $dependency = new PackageSource('org/package@repo');
            $project->addDependency('package', $dependency);
            $this->assertTrue($project->dependencyExists('package'));
            $this->assertIsArray($project->getDependencies());
            $this->assertCount(1, $project->getDependencies());
        }

        public function testAddDependencyAlreadyExists(): void
        {
            $data = [
                'dependencies' => ['org/package@repo']
            ];
            $project = new Project($data);
            
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('A dependency with the name \'package\' already exists');
            $project->addDependency('package', 'org/package@repo');
        }

        public function testRemoveDependency(): void
        {
            $data = [
                'dependencies' => ['org/package1@repo', 'org/package2@repo']
            ];
            $project = new Project($data);
            
            $project->removeDependency('package1');
            $this->assertFalse($project->dependencyExists('package1'));
            $this->assertTrue($project->dependencyExists('package2'));
            $this->assertCount(1, $project->getDependencies());
        }

        public function testRemoveDependencyNotExists(): void
        {
            $project = new Project([]);
            
            // removeDependency doesn't throw an exception if the dependency doesn't exist
            // It just silently does nothing
            $project->removeDependency('nonexistent');
            $this->assertNull($project->getDependencies());
        }

        public function testAddMultipleDependencies(): void
        {
            $project = new Project([]);
            
            $project->addDependency('package1', 'org/package1@repo');
            $project->addDependency('package2', 'org/package2@repo');
            
            $this->assertCount(2, $project->getDependencies());
            $this->assertTrue($project->dependencyExists('package1'));
            $this->assertTrue($project->dependencyExists('package2'));
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

        public function testAddMultipleBuildConfigurations(): void
        {
            $project = new Project([]);
            
            $config1 = new BuildConfiguration([
                'name' => 'debug',
                'output' => 'out',
                'type' => 'ncc'
            ]);
            $config2 = new BuildConfiguration([
                'name' => 'release',
                'output' => 'out',
                'type' => 'ncc'
            ]);
            $project->addBuildConfiguration($config1);
            $project->addBuildConfiguration($config2);
            
            $this->assertCount(2, $project->getBuildConfigurations());
            $this->assertTrue($project->buildConfigurationExists('debug'));
            $this->assertTrue($project->buildConfigurationExists('release'));
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
                'execution_units' => [
                    [
                        'name' => 'main.php',
                        'entry' => 'main.php'
                    ]
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
            $data = [
                'default_build' => '',
                'build_configurations' => [
                    [
                        'name' => 'release',
                        'output' => 'out',
                        'type' => 'ncc'
                    ]
                ]
            ];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The default build configuration must be a non-empty string if set');
            Project::validateArray($data);
        }

        public function testValidateArrayInvalidEntryPoint(): void
        {
            $data = [
                'entry_point' => '',
                'execution_units' => [
                    [
                        'name' => 'main.php',
                        'entry' => 'main.php'
                    ]
                ]
            ];
            
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
            Project::validateArray($data);
        }

        public function testValidateArrayInvalidBuildConfigurations(): void
        {
            $data = ['build_configurations' => 'not-array'];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('The build configurations property must be an array of build configuration if it\'s set');
            Project::validateArray($data);
        }

        public function testValidateArrayInvalidBuildConfigurationItem(): void
        {
            $data = ['build_configurations' => ['not-array']];
            
            $this->expectException(InvalidPropertyException::class);
            $this->expectExceptionMessage('Each build configuration must be an array');
            Project::validateArray($data);
        }

        /**
         * Test dependency management with multiple operations
         */
        public function testDependencyManagement(): void
        {
            $project = new Project([]);
            
            // Add dependencies
            $project->addDependency('dep1', 'org/dep1@repo');
            $project->addDependency('dep2', 'org/dep2=1.0@repo');
            $project->addDependency('dep3', new PackageSource('org/dep3=2.0@repo'));
            
            $this->assertCount(3, $project->getDependencies());
            $this->assertTrue($project->dependencyExists('dep1'));
            $this->assertTrue($project->dependencyExists('dep2'));
            $this->assertTrue($project->dependencyExists('dep3'));
            
            // Remove dependency
            $project->removeDependency('dep2');
            $this->assertCount(2, $project->getDependencies());
            $this->assertFalse($project->dependencyExists('dep2'));
        }

        /**
         * Test build configuration management
         */
        public function testBuildConfigurationManagement(): void
        {
            $project = new Project([]);
            
            $this->assertEmpty($project->getBuildConfigurations());
            
            $buildConfig = new BuildConfiguration([
                'name' => 'test-build',
                'output' => 'build/test',
                'type' => 'ncc'
            ]);
            
            $project->addBuildConfiguration($buildConfig);
            $this->assertCount(1, $project->getBuildConfigurations());
            $this->assertTrue($project->buildConfigurationExists('test-build'));
        }

        /**
         * Test getDefaultBuildConfiguration
         */
        public function testGetDefaultBuildConfiguration(): void
        {
            $project = new Project([
                'default_build' => 'release',
                'build_configurations' => [
                    [
                        'name' => 'release',
                        'output' => 'build/release',
                        'type' => 'ncc'
                    ]
                ]
            ]);
            
            $this->assertNotNull($project->getDefaultBuild());
            $this->assertEquals('release', $project->getDefaultBuild());
        }

        /**
         * Test project with null dependencies
         */
        public function testProjectWithNullDependencies(): void
        {
            $project = new Project([]);
            $this->assertNull($project->getDependencies());
            
            $project->addDependency('test', 'org/test@repo');
            $this->assertIsArray($project->getDependencies());
            $this->assertCount(1, $project->getDependencies());
        }

        /**
         * Test validateArray with valid complete data
         */
        public function testValidateArrayWithValidCompleteData(): void
        {
            $data = [
                'source' => 'src',
                'default_build' => 'release',
                'entry_point' => 'main',
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
                'dependencies' => ['org/dep@repo'],
                'execution_units' => [
                    [
                        'name' => 'main',
                        'type' => 'php',
                        'entry' => 'main.php',
                        'mode' => 'auto'
                    ]
                ],
                'build_configurations' => [
                    [
                        'name' => 'release',
                        'output' => 'build',
                        'type' => 'ncc'
                    ]
                ]
            ];
            
            // Should not throw any exception
            Project::validateArray($data);
            $this->assertTrue(true);
        }

        /**
         * Test project with empty build configurations array
         */
        public function testProjectWithEmptyBuildConfigurations(): void
        {
            $project = new Project(['build_configurations' => []]);
            $this->assertEmpty($project->getBuildConfigurations());
            $this->assertIsArray($project->getBuildConfigurations());
        }
    }

