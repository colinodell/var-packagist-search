<?php

namespace ColinODell\VarPackagistSearch;

class PopularPackages
{
    /**
     * @param int $limit
     *
     * @return string[]
     */
    public static function getList($limit)
    {
        $cacheFile = sprintf(__DIR__.'/../tmp/cache.top-%d-packages.json', $limit);
        if (file_exists($cacheFile)) {
            return json_decode(file_get_contents($cacheFile), true);
        }

        $packagesPerPage = 15;
        $pages = range(1, ceil($limit / $packagesPerPage));

        $packages = [];
        foreach ($pages as $page) {
            $json = json_decode(file_get_contents('https://packagist.org/explore/popular.json?page='.$page), true);
            foreach ($json['packages'] as $package) {
                $packages[] = $package['name'];
            }
        }

        $packages = array_slice($packages, 0, $limit);

        file_put_contents($cacheFile, json_encode($packages));

        return $packages;
    }
}
