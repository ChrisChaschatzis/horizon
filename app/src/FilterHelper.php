<?php

class FilterHelper {
    public static function buildWhereClause($filters, $baseAlias = 'p') {
        $where = ["$baseAlias.dataset_id = :dataset_id"];
        $params = ['dataset_id' => $filters['dataset_id']];

        // Date range
        if (!empty($filters['date_from'])) {
            $where[] = "$baseAlias.signature_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "$baseAlias.signature_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        // Coordinator Country (multi)
        if (!empty($filters['coordinator_country'])) {
            $countries = is_array($filters['coordinator_country']) ? $filters['coordinator_country'] : explode(',', $filters['coordinator_country']);
            // If comma separated string, handle it
            if (count($countries) > 0) {
                 $inQuery = "";
                 foreach ($countries as $k => $c) {
                     $key = "cc_$k";
                     $inQuery .= ":$key,";
                     $params[$key] = trim($c);
                 }
                 $inQuery = rtrim($inQuery, ',');
                 $where[] = "$baseAlias.coordinator_country IN ($inQuery)";
            }
        }

        // Toggles
        // Check for 'true', '1', or boolean true
        $isTrue = function($val) {
            return $val === 'true' || $val === '1' || $val === true;
        };

        if (!empty($filters['only_greek_any_role']) && $isTrue($filters['only_greek_any_role'])) {
            $where[] = "$baseAlias.has_greek_any_role = 1";
        }
        if (!empty($filters['only_greek_coordinator']) && $isTrue($filters['only_greek_coordinator'])) {
            $where[] = "$baseAlias.is_greek_coordinator = 1";
        }
        if (!empty($filters['exclude_error']) && $isTrue($filters['exclude_error'])) {
            $where[] = "($baseAlias.error_text IS NULL OR $baseAlias.error_text = '')";
        }

        return ['sql' => implode(' AND ', $where), 'params' => $params];
    }
}
