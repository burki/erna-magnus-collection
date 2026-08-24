<?php

// src/Service/CardService.php

namespace App\Service;

class CardService
{
    protected string $cardDir = '/a-z';
    protected string $cardExt = '.jpg';

    public function __construct(protected string $projectDir) {}

    protected function getPublicDir(): string
    {
        return $this->projectDir . '/public';
    }

    protected function getDataDir(): string
    {
        return $this->projectDir . '/data';
    }

    public function getCardImgDir(): string
    {
        return $this->getPublicDir() . $this->cardDir;
    }

    public function getCardDataDir(): string
    {
        return $this->getDataDir() . $this->cardDir;
    }

    public function buildCardNames(): array
    {
        $fnames = [];
        foreach (glob($this->getCardImgDir() . '/*' . $this->cardExt) as $fname) {
            $basename = basename($fname);
            $fnames[] = pathinfo($basename, PATHINFO_FILENAME);
        }

        return $fnames;
    }

    public function getData(string $card): array|false
    {
        $dataFile = $this->getCardDataDir() . '/' . $card . '.json';
        if (!file_exists($dataFile)) {
            return false;
        }

        $data = json_decode(file_get_contents($dataFile), true);
        if (null === $data) {
            return false;
        }

        return $data;
    }

    public function getNormalizedData(string $card, bool $resolveReferences = true): array|false
    {
        $dataFile = $this->getCardDataDir() . '/' . $card . '_normalized.json';
        if (!file_exists($dataFile)) {
            return false;
        }

        $data = json_decode(file_get_contents($dataFile), true);
        if (null === $data) {
            return false;
        }

        if ($resolveReferences && array_key_exists('references', $data) && 0 != ($offset = intval($data['references']))) {
            $cardNames = $this->buildCardNames();
            $cardIdx = array_search($card, $cardNames);
            if (false !== $cardIdx && $cardIdx + $offset >= 0 && $cardIdx + $offset < count($cardNames)) {
                return $this->getNormalizedData($cardNames[$cardIdx + $offset]);
            }
        }

        return $data;
    }

    public function saveNormalizedData(string $card, array $data): bool
    {
        $dataFile = $this->getCardDataDir() . '/' . $card . '_normalized.json';

        return file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Get normalized data for all cards.
     *
     * @param bool $resolveReferences whether to resolve references to other cards
     *
     * @return array an associative array of normalized data for all cards
     */
    public function getNormalized(bool $resolveReferences = true): array
    {
        $normalizedData = [];

        $cardNames = $this->buildCardNames();
        foreach ($cardNames as $cardName) {
            $data = $this->getNormalizedData($cardName, $resolveReferences);
            if (false === $data) {
                $data = $this->normalizeData($this->getData($cardName));

                if (false === $data) {
                    continue; // skip cards with no data
                }
            }

            $keys = array_keys($data);
            if (1 === count($keys) && 'references' === $keys[0]) {
                // skip cards that only contain references
                continue;
            }

            if (array_key_exists('birth', $data) && is_array($data['birth']) && 0 === count($data['birth'])) {
                unset($data['birth']);
            }
            if (array_key_exists('death', $data) && is_array($data['death']) && 0 === count($data['death'])) {
                unset($data['death']);
            }

            $normalizedData[$cardName] = $data;
        }

        return $normalizedData;
    }

    private function removeNullOrEmptyString($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = $this->removeNullOrEmptyString($haystack[$key]);
            }

            if (is_null($haystack[$key]) || (is_string($haystack[$key]) && '' === trim($haystack[$key]))) {
                unset($haystack[$key]);
            }
        }

        return $haystack;
    }

    private function normalizeDate(string $date, string $event): ?string
    {
        if ('death' === $event && 'gest.' == $date) {
            return null;
        }

        if (preg_match('/^(.+)\.(.+)\.(.+)$/', $date, $matches)) {
            // use two digits for day and month
            for ($i = 1; $i <= 2; ++$i) {
                if (preg_match('/^\d+$/', $matches[$i])) {
                    $matches[$i] = sprintf('%02d', $matches[$i]);
                }
                if ('X' === $matches[$i]) {
                    $matches[$i] = 'XX';
                }
            }
            $date = join('-', array_reverse(array_slice($matches, 1, 3)));
        }

        // convert trailing . to X for incomplete years, e.g. 19.. -> 19XX, 179.. -> 179X
        if (preg_match('/^\d+\.\.+$/', $date) || preg_match('/^\d{3}\.+$/', $date)) {
            $date = str_replace('.', 'X', $date);
        }

        return $date;
    }

    public function flattenStructure(array $data, string $key): string
    {
        $data = array_filter($data, fn($value) => !is_null($value));

        switch ($key) {
            case 'memberships':
                // flatten memberships into a single line
                $keys = array_keys($data);
                if (3 === count($keys) && !array_diff($keys, ['organization', 'role', 'year'])) {
                    return sprintf('%s  %s der %s', $data['year'], $data['role'], $data['organization']);
                }
                break;

            case 'literature':
                // flatten literature into a single line
                if (array_key_exists('pages', $data) && is_array($data['pages'])) {
                    $data['pages'] = join(', ', $data['pages']);
                }
                break;

            default:
                $keys = array_keys($data);
                if (1 === count($keys)) {
                    $data = $data[$keys[0]];
                    if (!is_array($data)) {
                        $data = [$data];
                    }
                }
        }

        $value_types = array_map(fn($value) => gettype($value), $data);
        if (in_array('array', $value_types)) {
            dd($data); // TODO: flatten
        }

        return join(' ', array_values($data));
    }

    public function normalizeData(array $data): array
    {
        if (array_key_exists('name', $data)) {
            if (is_string($data['name'])) {
                $data['name'] = ['family' => $data['name']];
            } else {
                foreach (['family', 'given'] as $part) {
                    if (array_key_exists($part . '_name', $data['name'])) {
                        $data['name'][$part] = $data['name'][$part . '_name'];
                        unset($data['name'][$part . '_name']);
                    }
                }
            }
        } else {
            // unstructured name data, try to extract name parts from top-level keys
            foreach (['family', 'given'] as $part) {
                if (array_key_exists($part . '_name', $data)) {
                    if (!array_key_exists('name', $data)) {
                        $data['name'] = [];
                    }
                    $data['name'][$part] = $data[$part . '_name'];
                    unset($data[$part . '_name']);
                }
            }
        }

        foreach (['birth', 'death'] as $event) {
            if (array_key_exists($event, $data)) {
                if (is_array($data[$event])) {
                    if (array_key_exists('year', $data[$event])) {
                        $year = $data[$event]['year'];
                        if (2 === strlen($year)) {
                            $year = $year . 'XX';
                        }
                        $data[$event]['date'] = $year;

                        unset($data[$event]['year']);
                    }

                    if (array_key_exists('date', $data[$event]) && !is_null($data[$event]['date'])) {
                        $data[$event]['date'] = $this->normalizeDate($data[$event]['date'], $event);
                    }

                    if ('birth' === $event && array_key_exists('place', $data[$event])
                        && (str_starts_with($data[$event]['place'], 'geb.') || str_starts_with($data[$event]['place'], 'gest.'))) {
                        unset($data[$event]['place']);
                    }
                } else {
                    if (preg_match('/^d/', $data[$event])) {
                        $data[$event] = ['date' => $this->normalizeDate($data[$event], $event)];
                    } else {
                        $data[$event] = ['place' => $data[$event]];
                    }
                }
            }

        }

        if (array_key_exists('parents', $data)) {
            if (!array_key_exists('relations', $data)) {
                $data['relations'] = [];
            }

            if (is_array($data['parents'])) {
                // https://localhost:8000/card/ernamagnuscollec01magn_0365
                foreach (['father', 'mother'] as $role) {
                    if (array_key_exists($role, $data['parents'])) {
                        $data['relations'][] = [
                            'role' => $role,
                            'name' => $data['parents'][$role],
                        ];
                    }
                }
            } else {
                $data['relations'][] = [
                    'role' => 'parents',
                    'name' => $data['parents'],
                ];
            }
        }

        if (array_key_exists('spouse', $data)) {
            if (!array_key_exists('relations', $data)) {
                $data['relations'] = [];
            }

            if (is_array($data['spouse'])) {
                if (array_key_exists('name', $data['spouse'])) {
                    $data['relations'][] = [
                        'role' => 'spouse',
                        'name' => $data['spouse']['name'],
                    ];
                }

                if (array_key_exists('additional_spouse', $data['spouse'])) {
                    // e.g. https://localhost:8000/card/ernamagnuscollec01magn_0349
                    $data['relations'][] = [
                        'role' => 'spouse',
                        'name' => $data['spouse']['additional_spouse'],
                    ];
                }

            } elseif (is_string($data['spouse']) && '' !== trim($data['spouse'])) {
                $data['relations'][] = ['role' => 'spouse', 'name' => trim($data['spouse'])];
            }

            unset($data['spouse']);
        }

        // normalize into details
        if (array_key_exists('details', $data)) {
            if (is_string($data['details'])) {
                $data['details'] = [$data['details']];
            }
        }

        if (array_key_exists('occupation', $data) && !array_key_exists('profession', $data)) {
            $data['profession'] = $data['occupation'];
            unset($data['occupation']);
        }

        foreach (['membership', 'memberships', 'degrees', 'post_degree_activity', 'career', 'positions', 'dates', 'dates_and_places'] as $detailKey) {
            // overstructured data, e.g. https://localhost:8000/card/ernamagnuscollec01magn_0367
            if (array_key_exists($detailKey, $data)) {
                if (is_array($data[$detailKey])) {
                    if (!array_is_list($data[$detailKey])) {
                        $data[$detailKey] = $this->flattenStructure($data[$detailKey], $detailKey);
                    } else {
                        // list, flatten each entry if needed
                        foreach ($data[$detailKey] as $i => $item) {
                            if (is_array($item)) {
                                $data[$detailKey][$i] = $this->flattenStructure($item, $detailKey);
                            }
                        }
                    }
                }
            }
        }

        foreach (['profession', 'degrees', 'post_degree_activity', 'career', 'activity', 'activities', 'additional_activity',
            'dates', 'dates_and_places',
            'membership', 'memberships', 'affiliations', 'career_highlights', 'position', 'positions', 'other_roles',
            'legacy', 'description',
            'information', 'additional_information', 'additional_info', 'relevant_information',
            'other', 'other_information', 'notes'] as $detailKey) {
            if (array_key_exists($detailKey, $data)) {
                if (!array_key_exists('details', $data)) {
                    $data['details'] = [];
                }

                if (is_array($data[$detailKey])) {
                    $data['details'] = array_merge($data['details'], $data[$detailKey]);
                } else {
                    if ('profession' === $detailKey) {
                        continue; // keep single line profess as top-level key
                    }

                    $data['details'][] = $data[$detailKey];
                }

                unset($data[$detailKey]);
            }
        }

        foreach (['publications', 'literature'] as $key) {
            if (array_key_exists($key, $data) && !is_null($data[$key])) {
                $entries = $data[$key];
                if (is_array($data[$key]) && !array_is_list($data[$key])) {
                    $entries = [$data[$key]];
                } elseif (is_string($data[$key])) {
                    $entries = [$data[$key]];
                }

                $entriesFiltered = [];
                foreach ($entries as $item) {
                    if (!is_string($item)) {
                        $keys = array_keys($item);
                        if (2 === count($keys) && in_array('source', $keys) && in_array('year', $keys)) {
                            $item = join(' ', [$item['source'], $item['year']]);
                        } elseif (1 === count($keys) && in_array('source', $keys)) {
                            $item = $item['source'];
                        } else {
                            $item = $this->flattenStructure($item, $key);
                        }
                    }

                    if (str_starts_with($item, 'Lit.:')) {
                        $item = substr($item, strlen('Lit.:'));
                    }
                    if (str_starts_with($item, 'Qu.:')) {
                        $item = substr($item, strlen('Qu.:'));
                    }

                    if ('' !== trim($item)) {
                        $entriesFiltered[] = trim($item);
                    }
                }

                $data[$key] = count($entriesFiltered) > 0 ? $entriesFiltered : null;
            }
        }


        if (array_key_exists('additional_info', $data)) {
            if (!array_key_exists('details', $data)) {
                $data['details'] = [];
            }

            if (is_array($data['additional_info'])) {
                $data['details'] = array_merge($data['details'], $data['additional_info']);
            } else {
                $data['details'][] = $data['additional_info'];
            }

            unset($data['additional_info']);
        }

        return $this->removeNullOrEmptyString($data);
    }
}
