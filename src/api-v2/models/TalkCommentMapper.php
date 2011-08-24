<?php

class TalkCommentMapper extends ApiMapper {
    public function getDefaultFields() {
        $fields = array(
            'rating' => 'rating',
            'comment' => 'comment',
            'user_display_name' => 'full_name',
            'created_date' => 'date_made'
            );
        return $fields;
    }

    public function getVerboseFields() {
        $fields = array(
            'rating' => 'rating',
            'comment' => 'comment',
            'user_display_name' => 'full_name',
            'source' => 'source',
            'created_date' => 'date_made'
            );
        return $fields;
    }

    public function getCommentsByTalkId($talk_id, $resultsperpage, $start, $verbose = false) {
        $sql = $this->getBasicSQL();
        $sql .= 'and talk_id = :talk_id';

        $sql .= $this->buildLimit($resultsperpage, $start);
        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':talk_id' => $talk_id
            ));
        if($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $retval = $this->transformResults($results, $verbose);
            return $retval;
        }
        return false;
    }

    public function getCommentById($comment_id, $verbose = false) {
        $sql = $this->getBasicSQL();
        $sql .= ' and tc.ID = :comment_id ';
        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':comment_id' => $comment_id
            ));
        if($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $retval = $this->transformResults($results, $verbose);
            return $retval;
        }
        return false;
    }

    public function transformResults($results, $verbose) {
        $list = parent::transformResults($results, $verbose);
        $host = $this->_request->host;

        // add per-item links 
        if (is_array($list) && count($list)) {
            foreach ($results as $key => $row) {
                $list[$key]['uri'] = 'http://' . $host . '/v2/talk_comments/' . $row['ID'];
                $list[$key]['verbose_uri'] = 'http://' . $host . '/v2/talk_comments/' . $row['ID'] . '?verbose=yes';
                $list[$key]['talk_uri'] = 'http://' . $host . '/v2/talks/' 
                    . $row['talk_id'];
                $list[$key]['talk_comments_uri'] = 'http://' . $host . '/v2/talks/' 
                    . $row['talk_id'] . '/comments';
                if($row['user_id']) {
                    $list[$key]['user_uri'] = 'http://' . $host . '/v2/users/' 
                        . $row['user_id'];
                }
            }

            if (count($list) > 1) {
                $list = $this->addPaginationLinks($list); 
            }
        }
        return $list;
    }

    protected function getBasicSQL() {
        $sql = 'select tc.*, user.full_name '
            . 'from talk_comments tc '
            . 'left join user on tc.user_id = user.ID '
            . 'where tc.active = 1 '
            . 'and tc.private <> 1 ';
        return $sql;
    }
}