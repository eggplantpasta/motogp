<?php

namespace MotoGp;

use Webmin\Database;

class Country
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getCountries(): array
    {
        $sql = '
            select *
            from countries
            order by name
        ';

        return $this->db->query($sql);
    }

    public function getCountriesSelected(string|int $selectedCode): array
    {
        $selectedCode = (string)$selectedCode;
        $countries = $this->getCountries();

        return array_map(function (array $country) use ($selectedCode): array {
            $country['selected'] = isset($country['country_code']) && (string)$country['country_code'] === $selectedCode;
            return $country;
        }, $countries);
    }

}
