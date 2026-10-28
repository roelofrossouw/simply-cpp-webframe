cmake_minimum_required(VERSION 3.22)
set(SC_MODULE webframe)
project(simply-cpp-${SC_MODULE} HOMEPAGE_URL "https://github.com/roelofrossouw/simply-cpp-webframe" LANGUAGES NONE)
# A PHP library: nothing is compiled. CMake versions it, runs its tests with PHP, installs it and makes the package.
# Its files go where Debian's PHP finds them, so the prefix is /usr unless one is given.
if (CMAKE_INSTALL_PREFIX_INITIALIZED_TO_DEFAULT AND NOT APPLE)
    set(CMAKE_INSTALL_PREFIX /usr CACHE PATH "Install prefix" FORCE)
endif ()
include(cmake/sc_bootstrap.cmake)
include(GNUInstallDirs)

option(SC_WEBFRAME_BUILD_TESTS "Run simply-cpp-webframe's tests" ${PROJECT_IS_TOP_LEVEL})

get_sc_version()
message(STATUS "SimplyCPP ${SC_MODULE} Version: ${SC_VERSION}")

## PHP ##
# The CLI runs the tests; sites run it under PHP-FPM or Apache's module. It needs PHP 8.3 or later (noble, resolute).
find_program(SC_WEBFRAME_PHP NAMES php)
if (NOT SC_WEBFRAME_PHP AND NOT APPLE)
    message(STATUS "No php found, installing php-cli")
    execute_process(COMMAND sudo apt-get install -y php-cli php-pgsql php-curl php-gd php-mbstring)
    unset(SC_WEBFRAME_PHP CACHE)
    find_program(SC_WEBFRAME_PHP NAMES php)
endif ()
if (NOT SC_WEBFRAME_PHP)
    message(FATAL_ERROR "No php found. Install php-cli (apt) or php (brew), or pass -DSC_WEBFRAME_PHP=/path/to/php")
endif ()
execute_process(COMMAND "${SC_WEBFRAME_PHP}" -r "echo PHP_VERSION;" OUTPUT_VARIABLE SC_WEBFRAME_PHP_VERSION COMMAND_ERROR_IS_FATAL ANY)
if (SC_WEBFRAME_PHP_VERSION VERSION_LESS 8.3)
    message(FATAL_ERROR "sc-webframe needs PHP 8.3 or later, ${SC_WEBFRAME_PHP} is ${SC_WEBFRAME_PHP_VERSION}")
endif ()
message(STATUS "Testing with PHP ${SC_WEBFRAME_PHP_VERSION} (${SC_WEBFRAME_PHP})")
# The extensions the package depends on, which the tests use too (a build server may have only php-cli)
execute_process(COMMAND "${SC_WEBFRAME_PHP}" -m OUTPUT_VARIABLE SC_WEBFRAME_PHP_MODULES)
set(SC_WEBFRAME_MISSING)
foreach (extension pgsql curl gd mbstring)
    if (NOT SC_WEBFRAME_PHP_MODULES MATCHES "(^|\n)${extension}(\n|$)")
        list(APPEND SC_WEBFRAME_MISSING php-${extension})
    endif ()
endforeach ()
if (SC_WEBFRAME_MISSING AND NOT APPLE)
    message(STATUS "Installing ${SC_WEBFRAME_MISSING} for the tests")
    execute_process(COMMAND sudo apt-get install -y ${SC_WEBFRAME_MISSING})
elseif (SC_WEBFRAME_MISSING)
    message(WARNING "PHP lacks ${SC_WEBFRAME_MISSING}: some tests will fail")
endif ()

###########
# Install #
###########
# The classes and autoload.php on PHP's include_path (require_once 'sc-webframe/autoload.php'); the pages, scripts and
# styles a site serves under /framework; the schema; the Hello world example.
set(SC_WEBFRAME_DOC ${CMAKE_INSTALL_DATADIR}/doc/simply-cpp-webframe)
install(DIRECTORY php/ DESTINATION ${CMAKE_INSTALL_DATADIR}/php/sc-webframe)
install(DIRECTORY public/ DESTINATION ${CMAKE_INSTALL_DATADIR}/sc-webframe/public)
install(DIRECTORY sql/ DESTINATION ${CMAKE_INSTALL_DATADIR}/sc-webframe/sql)
install(DIRECTORY examples/ DESTINATION ${SC_WEBFRAME_DOC}/examples)
install(FILES README.md NOTICE DESTINATION ${SC_WEBFRAME_DOC})
install(FILES LICENSE DESTINATION ${SC_WEBFRAME_DOC} RENAME copyright)

#########
# Tests #
#########
if (SC_WEBFRAME_BUILD_TESTS)
    enable_testing()
    # Every file parses, warnings included
    add_test(NAME test-php-lint COMMAND "${SC_WEBFRAME_PHP}" "${CMAKE_CURRENT_SOURCE_DIR}/tests/lint.php" "${CMAKE_CURRENT_SOURCE_DIR}")
    # Each tests/test-*.php, with the source tree's php/ first on the include_path, as installed
    file(GLOB SC_WEBFRAME_TESTS CONFIGURE_DEPENDS ${CMAKE_CURRENT_SOURCE_DIR}/tests/test-*.php)
    foreach (test ${SC_WEBFRAME_TESTS})
        get_filename_component(name "${test}" NAME_WE)
        add_test(NAME ${name} COMMAND "${SC_WEBFRAME_PHP}" -d error_reporting=-1 -d display_errors=1 "${test}"
                WORKING_DIRECTORY "${CMAKE_CURRENT_SOURCE_DIR}/tests")
        set_tests_properties(${name} PROPERTIES LABELS unit FAIL_REGULAR_EXPRESSION "FAILED|Deprecated|Warning|Fatal error")
    endforeach ()
    set_tests_properties(test-php-lint PROPERTIES LABELS unit)
endif ()

#############
# Packaging #
#############
# One package for every release (Architecture: all); the version carries the release like the other modules'.
if (APPLE)
    set(SC_WEBFRAME_CODENAME apple)
    set(CPACK_GENERATOR TGZ)
elseif (UNIX)
    execute_process(COMMAND lsb_release -sc OUTPUT_VARIABLE SC_WEBFRAME_CODENAME OUTPUT_STRIP_TRAILING_WHITESPACE)
    set(CPACK_GENERATOR DEB)
endif ()
set(CPACK_PACKAGE_NAME simply-cpp-webframe)
set(CPACK_PACKAGE_VERSION "${SC_VERSION}~${SC_WEBFRAME_CODENAME}")
set(CPACK_PACKAGE_CONTACT "Roelof Rossouw")
set(CPACK_PACKAGE_DESCRIPTION_SUMMARY "PHP web framework: HTML and Bootstrap 5 classes, pages, menu, access and sign-in")
set(CPACK_DEBIAN_PACKAGE_MAINTAINER "Roelof Rossouw <roelof@roelof.co.za>")
set(CPACK_DEBIAN_PACKAGE_DESCRIPTION "A PHP web framework for Ubuntu ${SC_WEBFRAME_CODENAME}: pages built from HTML and Bootstrap 5 classes, a menu and access levels in PostgreSQL, and sign-in with Google, Microsoft, Apple, GitHub or Facebook")
set(CPACK_DEBIAN_PACKAGE_SECTION php)
set(CPACK_DEBIAN_PACKAGE_ARCHITECTURE all)
set(CPACK_DEBIAN_FILE_NAME DEB-DEFAULT)
set(CPACK_DEBIAN_PACKAGE_HOMEPAGE "${PROJECT_HOMEPAGE_URL}")
# PHP 8.3 or later (the typed class constants), pgsql for the storage, curl for the sign-in providers, gd for profile
# photos, mbstring for names
set(CPACK_DEBIAN_PACKAGE_DEPENDS "php-common (>= 2:93~), php-pgsql, php-curl, php-gd, php-mbstring")
set(CPACK_DEBIAN_PACKAGE_SUGGESTS "php-fpm, apache2, postgresql-client")
# postinst makes the folder the sign-in providers' keys are cached in, for www-data; purging removes it
set(CPACK_DEBIAN_PACKAGE_CONTROL_EXTRA "${CMAKE_CURRENT_SOURCE_DIR}/packaging/postinst;${CMAKE_CURRENT_SOURCE_DIR}/packaging/postrm")
set(CPACK_DEBIAN_PACKAGE_CONTROL_STRICT_PERMISSION ON)
set(CPACK_SET_DESTDIR ON)
set(CPACK_PACKAGE_DIRECTORY "${CMAKE_BINARY_DIR}")
include(CPack)
