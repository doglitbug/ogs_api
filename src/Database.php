<?php

/**
 * A wrapper object around all database specific functions in case we need to change database later on
 */
class Database
{
    #region misc
    private mysqli $connection;

    public function connect(): void
    {
        try {
            $this->connection = new mysqli($_ENV["DATABASE_HOST"], $_ENV["DATABASE_USER"], $_ENV["DATABASE_PASS"], $_ENV["DATABASE_NAME"]);
        } catch (Exception $e) {
            error(500, "Could not open connection to database", $e);
        }
    }

    /** Close the database connection if needed
     * @return void
     */
    public function disconnect(): void
    {
        if (isset($this->connnection)) {
            $this->connection->close();
        }
    }

    /**
     * Sanitize the provided string to prevent SQL injection
     * @param string $string input
     * @return string Cleaned string
     */
    public function escape(string $string): string
    {
        //Removed FILTER_FLAG_STRIP_LOW so that \r\n are not turned into &#13;&#10;
        $string = filter_var($string, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);
        return $this->connection->real_escape_string($string);
    }

    /**
     * Perform a get query and return an assoc array of results
     * @param string $query Parameterized query string
     * @param string $types String of value types, eg "sss"
     * @param array $values Array of values
     * @param array $data paginate: Use pagination to return only a subset
     * @return array|null Results or empty array
     */
    public function get_query(string $query, string $types = "", array $values = [], array $data = []): array|null
    {
        // if (isset($options['paginate'])) {
        //     list($page, $size) = get_page_and_size();
        //     $offset = ($page - 1) * $size;
        //     $query .= "\nLIMIT $offset, $size";
        // }

        try {
            //Prepare
            $statement = $this->connection->prepare($query);
            //Bind
            if ($types) {
                $statement->bind_param($types, ...$values);
            }
            //Execute
            $statement->execute();
            //Retrieve
            $results = $statement->get_result()?->fetch_all(MYSQLI_ASSOC);
            //Close
            $statement->close();
        } catch (Exception $e) {
            error(500, "Error getting data", $e);
        }

        return $results;
    }

    /**
     * Perform an insert query and return new id
     * @param string $query Escaped query string
     * @return int new ID
     */
    public function insert_query(string $query, string $types = "", array $values = []): int
    {
        try {
            //Prepare
            $statement = $this->connection->prepare($query);
            //Bind
            if ($types) {
                $statement->bind_param($types, ...$values);
            }
            //Execute
            $statement->execute();
            //Retrieve
            $results = $this->connection->insert_id;
            //Close
            $statement->close();
        } catch (Exception $e) {
            error(500, "Error inserting data", $e);
        }

        return $results;
    }

    /**
     * Perform an update query
     * @param string $query Parameterized query string
     * @param string $types String of value types, eg "sss"
     * @param array $values Array of values
     * @return void
     */
    public function update_query(string $query, string $types = "", array $values = []): void
    {
        try {
            //Prepare
            $statement = $this->connection->prepare($query);
            //Bind
            if ($types) {
                $statement->bind_param($types, ...$values);
            }
            //Execute
            $statement->execute();
            //Close
            $statement->close();
        } catch (Exception $e) {
            error(500, "Error updating data", $e);
        }
    }

    /**
     * Perform a delete query
     * @param string $query Escaped query string
     */
    public function delete_query(string $query, string $types = "", array $values = []): void
    {
        try {
            //Prepare
            $statement = $this->connection->prepare($query);
            //Bind
            if ($types) {
                $statement->bind_param($types, ...$values);
            }
            //Execute
            $statement->execute();
            //Close
            $statement->close();
        } catch (Exception $e) {
            error(500, "Error deleting data", $e);
        }
    }

    #endregion

    #region settings
    public function get_settings(): array
    {
        $query = <<<SQL
            SELECT name, value
            FROM settings
        SQL;

        return $this->get_query($query);
    }

    public function update_settings(array $settings): void
    {

    }

    #endregion
































    
    #region user
    /** Get all users
     * @param array $options search: Filter to search
     *                       paginate: Use pagination to return only a subset
     * @return array
     * @todo Change this to a full text search?
     */
    public function get_users(array $options = []): array
    {
        $types = "";
        $values = array();

        $query = <<<SQL
        SELECT  user_id,
                username,
                name,
                email,
                user.description,
                location.description as location,
                locked_out,
                IFNULL(admin.description, 'User') as access,
                user.created_at,
                user.updated_at
        FROM user
        LEFT JOIN location using (location_id)
        LEFT JOIN user_admin using (user_id)
        LEFT JOIN admin using (admin_id)
        SQL;

        $where_and = "WHERE";

        if (isset($options['search']) && $options['search']) {
            $options['search'] = '%' . $options['search'] . '%';
            $query .= <<<SQL
            
                $where_and username LIKE ?
                OR name LIKE ?
                OR email LIKE ?
            SQL;
            $types .= "sss";
            array_push($values, $options['search'], $options['search'], $options['search']);
            $where_and = "AND";
        }

        return $this->get_query($query, $types, $values, $options);
    }

    /** Get a user by ID
     * @param string $user_id
     * @return array|null User details
     */
    public function get_user_by_id(string $user_id): array|null
    {
        $query = <<<SQL
        SELECT  user_id,
                username,
                name,
                email,
                user.description,
                location_id,
                location.description as location,
                locked_out,
                IFNULL(admin.description, 'User') as access,
                user.created_at,
                user.updated_at
        FROM user
        LEFT JOIN location using (location_id)
        LEFT JOIN user_admin using (user_id)
        LEFT JOIN admin using (admin_id)
        WHERE user_id = ?
        LIMIT 1
        SQL;

        $result = $this->get_query($query, "s", [$user_id]);
        return $result ? $result[0] : null;
    }

    /** Get user by email address
     * @param string $email
     * @return array|null User details
     */
    public function get_user_by_email(string $email): array|null
    {
        $query = <<<SQL
        SELECT  user_id,
                username,
                name,
                email,
                location_id,
                location.description as location,
                locked_out,
                user.created_at,
                user.updated_at
        FROM user
        LEFT JOIN location using (location_id)
        WHERE email = ?
        LIMIT 1
        SQL;

        $result = $this->get_query($query, "s", [$email]);
        return $result ? $result[0] : null;
    }


    /** Add a new user to the database
     * @param array $user Requires username, name and email
     * @return int new user ID
     */
    public function insert_user(array $user): int
    {
        $query = <<<SQL
        INSERT INTO user
            (username, name, email)
            VALUES (?, ?, ?)
        SQL;

        return $this->insert_query($query, "sss", [$user['username'], $user['name'], $user['email']]);
    }

    /** Update an existing user
     * @param array $user
     * @return void
     */
    public function update_user(array $user): void
    {
        $query = <<<SQL
        UPDATE user SET username = ?,
                        name = ?,
                        email = ?,
                        description = ?,
                        location_id = ?,
                        locked_out = ?
        WHERE user_id = ?
        LIMIT 1
        SQL;

        $this->update_query(
            $query,
            "sssssss",
            [
                $user['username'],
                $user['name'],
                $user['email'],
                $user['description'],
                $user['location_id'],
                $user['locked_out'],
                $user['user_id']
            ]
        );
    }

    /** Check to see if there is a user in the database with this email
     * @param string $email
     * @return bool
     */
    public function check_email_exists(string $email): bool
    {
        return $this->get_user_by_email($email) !== null;
    }

    /** Checks to see that no other user has this email address
     * Likely to be used when admins change email addresses only
     * @param array $user Requires user_id and email
     * @return bool
     */
    public function has_unique_email(array $user): bool
    {
        $query = <<<SQL
            SELECT * FROM user
            WHERE email = ?
            AND user_id != ?
        SQL;

        $result = $this->get_query($query, "ss", [$user['email'], $user['user_id']]);
        return $result != null;
    }

    /** Checks to see that no other user has this username
     * @param array $user Requires user_id and username
     * @return bool
     */
    public function has_unique_username(array $user): bool
    {
        $query = <<<SQL
            SELECT * FROM user
            WHERE username = ?
            AND user_id != ?
        SQL;

        $result = $this->get_query($query, "ss", [$user['username'], $user['user_id']]);
        return $result != null;
    }

    /** Check to see if this user is Super Admin, Admin or ordinary User
     * @param string $user_id
     * @return string
     */
    public function get_access_level(string $user_id): string
    {
        $query = <<<SQL
        SELECT IFNULL(admin.description, 'User') as access
        FROM user
        LEFT JOIN user_admin using (user_id)
        LEFT JOIN admin using (admin_id)
        WHERE user_id = ?
        LIMIT 1
        SQL;

        $result = $this->get_query($query, "s", [$user_id]);
        //TODO Potential error if user not found?
        return $result[0]['access'];
    }

    #endregion

    #region garage
    /** Get all garages
     * @param array $options visible: Are they publicly visible?
     *                          search: Search term to filter by
     * @return array
     */
    public function get_garages(array $options = []): array
    {
        $types = "";
        $values = array();

        $query = <<<SQL
        SELECT  garage_id,
                name,
                garage.description,
                location.description as location,
                visible,
                garage.updated_at,
                garage.created_at
        FROM garage
        LEFT JOIN location using (location_id)
        SQL;

        $where_and = "WHERE";

        if (isset($options['visible'])) {
            $query .= <<<SQL
            
                $where_and visible = ?
            SQL;
            $types .= "s";
            $values[] = $options['visible'];
            $where_and = "AND";
        }

        if (isset($options['search']) && $options['search']) {
            $options['search'] = '%' . $options['search'] . '%';
            $query .= <<<SQL
            
                $where_and (name LIKE ?
                OR garage.description LIKE ?)
            SQL;
            $types .= "ss";
            array_push($values, $options['search'], $options['search']);
            $where_and = "AND";
        }

        return $this->get_query($query, $types, $values, $options);
    }


    /** Get all garages that this user has access to
     * @param string $user_id
     * @param array $options access: Owner or Worker
     * @return array|null
     * @todo Do this as an option/filter in get_all_garages?
     */
    public function get_garages_by_user(string $user_id, array $options = []): array|null
    {
        $types = "";
        $values = array();

        $query = <<<SQL
        SELECT  user_id,
                garage_id,
                garage.name,
                garage.description as description,
                access.description as access,
                location.description as location,
                visible
        FROM user_garage_access
            LEFT JOIN access using (access_id)
            LEFT JOIN garage using (garage_id)
            LEFT JOIN location using (location_id)
        WHERE user_id = ?
        SQL;

        $types .= "s";
        $values[] = $user_id;

        $where_and = "AND";
        if (isset($options['access'])) {
            $query .= <<<SQL

            $where_and access.description = ?
        SQL;
            $types .= "s";
            $values[] = $options['access'];
            $where_and = "AND";
        }

        return $this->get_query($query, $types, $values);
    }

    /** Get a list of owners, then workers for this garage
     * @param string $garage_id
     * @return array|null
     */
    public function get_garage_admin(string $garage_id): array|null
    {
        $query = <<<SQL
        SELECT  user_id,
                username,
                access.description as access
        FROM user_garage_access
            LEFT JOIN user USING (user_id)
            LEFT JOIN access using (access_id)
        WHERE garage_id = ?
        ORDER BY access.access_id
        SQL;

        return $this->get_query($query, "s", [$garage_id]);
    }

    /** Create a new garage
     * @param array $garage
     * @return int New ID
     * @note This does not set ownership or access at all!
     */
    public function insert_garage(array $garage): int
    {
        $query = <<<SQL
        INSERT INTO garage
            (name, description, location_id, visible)
        VALUES (?, ?, ?, ?)
        SQL;

        return $this->insert_query($query, "ssss", [
            $garage['name'],
            $garage['description'],
            $garage['location_id'],
            $garage['visible'],
        ]);
    }

    /** Update an existing garage
     * @param array $garage
     * @return void
     */
    public function update_garage(array $garage): void
    {
        $query = <<<SQL
        UPDATE garage SET   name = ?,
                            description = ?,
                            location_id = ?,
                            visible = ?
        WHERE garage_id = ?
        LIMIT 1
        SQL;

        $this->update_query(
            $query,
            "sssss",
            [
                $garage['name'],
                $garage['description'],
                $garage['location_id'],
                $garage['visible'],
                $garage['garage_id']
            ]
        );
    }

    /** Delete garage, this assumes that this action has only been called by user with authority etc.
     * user_garage_access rows will be deleted automatically!
     * @param array $garage
     * @return void
     */
    public function delete_garage(array $garage): void
    {
        $query = <<<SQL
        DELETE FROM garage
        WHERE garage_id = ?
        LIMIT 1;
        SQL;

        $this->delete_query($query, "s", [$garage['garage_id']]);
    }

    #endregion

    #region item
    /** Get items, usually from an individual garage with primary image
     * @param array $options garage_id: Filter to particular garage
     *                       search: Filter to search
     *                       visible: Hide hidden items (required for pagination to work)
     *                       paginate: Use pagination to return only a subset
     * @return array
     */
    public function get_items(array $options = []): array
    {
        $types = "";
        $values = array();

        $query = <<<SQL
        SELECT  item.item_id,
                garage_id,
                item.name,
                item.description,
                item.visible,
                item.updated_at,
                item.created_at,
                image.image_id,
                image.width,
                image.height,
                location.description as location,
                CONCAT(path, '/', filename) as source
        FROM item
        LEFT JOIN LATERAL (SELECT   *
                                    FROM item_image
                                    WHERE item.item_id = item_image.item_id
                                    ORDER BY main DESC
                                    LIMIT 1) as iii
        using (item_id)
        LEFT JOIN (SELECT   *
                            FROM image) as image using (image_id)
        LEFT JOIN garage USING (garage_id)
        LEFT JOIN location USING (location_id)
        SQL;

        $where_and = "WHERE";

        if (isset($options['garage_id'])) {
            $query .= <<<SQL
            
                $where_and garage_id = ?
            SQL;
            $types .= "s";
            $values[] = $options['garage_id'];
            $where_and = "AND";
        }

        if (isset($options['visible'])) {
            $query .= <<<SQL
            
            $where_and item.visible = '1' AND garage.visible = '1'
        SQL;
            $where_and = "AND";
        }

        if (isset($options['search']) && $options['search'] != "") {
            $query .= <<<SQL

                $where_and MATCH (item.name, item.description) AGAINST (?)
            SQL;
            $types .= "s";
            $values[] = $options['search'];
            $where_and = "AND";
        }

        return $this->get_query($query, $types, $values, $options);
    }

    /** Get an individual item, usually for show/edit item
     * @param string $item_id
     * @param array $options public: garage hidden will override visibility
     * @return array|null
     * @todo set visible query as an extra query??
     */
    public function get_item(string $item_id, array $options = []): array|null
    {
        $visible_query = isset($options['public']) ? "if (item.visible and garage.visible, true, false) as visible" : "item.visible";

        $query = <<<SQL
        SELECT  item.item_id,
                item.garage_id,
                item.name,
                item.description,
                $visible_query
        FROM item
        LEFT JOIN garage USING (garage_id)
        WHERE item_id = ?
        LIMIT 1
        SQL;

        $result = $this->get_query($query, "s", [$item_id]);
        return $result ? $result[0] : null;
    }

    /** Insert a new item
     * @param array $item
     * @return int
     */
    public function insert_item(array $item): int
    {
        $query = <<<SQL
            INSERT INTO item
            (garage_id, name, description, visible)
            VALUES  (?, ?, ?, ?)
        SQL;

        return $this->insert_query($query, "ssss", [$item['garage_id'], $item['name'], $item['description'], $item['visible']]);
    }

    public function update_item(array $item): void
    {
        $query = <<<SQL
        UPDATE item SET garage_id = ?,
                        name = ?,
                        description = ?,
                        visible = ?
        WHERE item_id = ?
        LIMIT 1
        SQL;

        $this->update_query($query, "sssss", [$item['garage_id'], $item['name'], $item['description'], $item['visible'], $item['item_id']]);
    }

    /** Delete an item, assumes item_image links have been removed
     * @param array $item
     * @return void
     */
    public function delete_item(array $item): void
    {
        $query = <<<SQL
        DELETE FROM item
        WHERE item_id = ?
        LIMIT 1;
        SQL;

        $this->delete_query($query, "s", [$item['item_id']]);
    }


    #endregion

    #region location
    /** Get all locations
     * @return array
     */
    public
        function get_locations(
    ): array {
        $query = <<<SQL
        SELECT  location_id,
                description
        FROM location
        ORDER BY description
        SQL;

        return $this->get_query($query);
    }

    #endregion

    #region user_garage_access
    /** Set access for user to garage (will update if already existing!)
     * @param string $user_id
     * @param string $garage_id
     * @param string $access
     * @return void
     */
    public function set_user_garage_access(string $user_id, string $garage_id, string $access): void
    {
        $query = <<<SQL
        REPLACE INTO user_garage_access
            (user_id, garage_id, access_id)
            VALUES (?,
                    ?,
                    (SELECT access_id FROM access WHERE description = ?))
        SQL;

        $this->insert_query($query, "sss", [$user_id, $garage_id, $access]);
    }

    /** Find this users access level for this garage
     * @param string $user_id
     * @param string $garage_id
     * @return string Owner|Worker|User
     */
    public function get_user_access(string $user_id, string $garage_id): string
    {
        $query = <<<SQL
        SELECT IFNULL((
            SELECT description
            FROM user_garage_access
            LEFT JOIN access using (access_id)
            WHERE user_id = ?
            AND garage_id = ?
            LIMIT 1),
        'User') access;
        SQL;
        return $this->get_query($query, "ss", [$user_id, $garage_id])[0]['access'];
    }

    #endregion

    #region image
    /** Get a single image from the database
     * @param string $image_id
     * @return array|null
     */
    public function get_image(string $image_id): array|null
    {
        $query = <<<SQL
        SELECT  image_id,
                width,
                height,
                CONCAT(path, '/', filename) as source
        FROM image
        WHERE image_id = ?
        SQL;

        $result = $this->get_query($query, "s", [$image_id]);
        return $result ? $result[0] : null;
    }

    /** Get the images for an item
     * @param string $item_id
     * @return array
     */
    public function get_item_images(string $item_id): array
    {
        $query = <<<SQL
        SELECT  item_id,
                image_id,
                main,
                width,
                height,
                CONCAT(path, '/', filename) as source
        FROM item_image
        JOIN image using (image_id) 
        WHERE item_id = ?
        ORDER BY main DESC
        SQL;

        return $this->get_query($query, "s", [$item_id]);
    }

    /** Insert a new image into the database, assumes it has been moved to the correct location
     * @param array $image
     * @return int image_id
     */
    public function insert_image(array $image): int
    {
        $query = <<<SQL
            INSERT INTO image
            (width, height, path, filename)
            VALUES (?, ?, ?, ?)
        SQL;

        return $this->insert_query($query, "ssss", [$image['width'], $image['height'], $image['path'], $image['filename']]);
    }

    /** Link an image to a garage
     * @param string $garage_id
     * @param string $image_id
     * @param string $main is this the main image?
     * @return void
     */
    public function insert_garage_image(string $garage_id, string $image_id, string $main = "0"): void
    {
        $query = <<<SQL
            INSERT IGNORE INTO garage_image
            (garage_id, image_id, main)
            VALUES (?, ?, ?)
        SQL;

        $this->insert_query($query, "sss", [$garage_id, $image_id, $main]);
    }

    /** Link an image to an item
     * @param string $item_id
     * @param string $image_id
     * @param string $main is this the main image?
     * @return void
     */
    public function insert_item_image(string $item_id, string $image_id, string $main = "0"): void
    {
        $query = <<<SQL
            INSERT IGNORE INTO item_image
            (item_id, image_id, main)
            VALUES (?, ?, ?)
        SQL;

        $this->insert_query($query, "sss", [$item_id, $image_id, $main]);
    }

    /** Remove image from database, assumes that the file has already been removed
     * This should also remove item/garage_item links
     * @param array $image
     * @return void
     */
    public function delete_image(array $image): void
    {
        $query = <<<SQL
        DELETE FROM image
        WHERE image_id = ?
        LIMIT 1;
        SQL;

        $this->delete_query($query, "s", [$image['image_id']]);
    }

    #endregion
}