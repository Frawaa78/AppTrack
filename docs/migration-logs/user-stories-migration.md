## Step-by-Step Migration Instructions for phpMyAdmin

### Step 1: Check the foreign key constraint name
Run this query to see the exact constraint name:

```sql
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'AppTrack' 
  AND TABLE_NAME = 'user_stories' 
  AND COLUMN_NAME = 'application_id';
```

### Step 2: Drop the foreign key constraint
Use the constraint name from Step 1 (likely `user_stories_ibfk_1`):

```sql
ALTER TABLE user_stories DROP FOREIGN KEY user_stories_ibfk_1;
```

### Step 3: Check for index
Check if there's an index on the column:

```sql
SHOW INDEX FROM user_stories WHERE Column_name = 'application_id';
```

If there's an index, drop it (replace `index_name` with the actual name):
```sql
ALTER TABLE user_stories DROP INDEX application_id;
```

### Step 4: Modify the column
Now you can safely change the column type:

```sql
ALTER TABLE user_stories MODIFY COLUMN application_id VARCHAR(500) NULL;
```

### Step 5: Verify the change
Check that the change was successful:

```sql
DESCRIBE user_stories;
```

### Step 6: Test with existing data
Make sure existing data is still intact:

```sql
SELECT id, title, application_id FROM user_stories WHERE application_id IS NOT NULL LIMIT 10;
```

## Important Notes:
- We are NOT recreating the foreign key constraint because the column will now store comma-separated values
- Foreign key constraints don't work with comma-separated values like "123,456,789"
- The application code will handle data validation instead
