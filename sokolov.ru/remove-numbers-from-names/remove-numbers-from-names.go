package main

import (
	"database/sql"
	"fmt"
	"regexp"
	"strings"

	_ "github.com/go-sql-driver/mysql"
)

var (
	product_code  string
	product_title string
	product_id    int
	skip          int
)

func checkErr(err error) {
	if err != nil {
		fmt.Println(err)
	}

}

func main() {
	re := regexp.MustCompile("\\s\\#\\d*\\.?\\d??$")
	db, err := sql.Open("mysql", "zol:FLGKhxQ3@/zol")
	checkErr(err)
	defer db.Close()

	err = db.Ping()
	db.SetMaxOpenConns(10000)
	checkErr(err)

	//	products, err := db.Query("SELECT DISTINCT product_code FROM zol.amcs_products LIMIT 5000 OFFSET 10000;")
	products, err := db.Query("SELECT DISTINCT product_code FROM zol.amcs_products")
	checkErr(err)
	defer products.Close()

	stmtProdID, err := db.Prepare("SELECT product_id FROM zol.amcs_products WHERE product_code = ? LIMIT 1;")
	checkErr(err)
	defer stmtProdID.Close()

	stmtProdTitle, err := db.Prepare("SELECT product FROM zol.amcs_product_descriptions WHERE product_id = ?;")
	// stmtProdTitle, err := db.Prepare("SELECT value FROM zol.amcs_product_features_values WHERE feature_id = 54 AND product_id = ?;")
	checkErr(err)
	defer stmtProdTitle.Close()

	stmtProdUpdate, err := db.Prepare("UPDATE zol.amcs_product_descriptions SET product=? WHERE product_id = ?;")
	// stmtProdUpdate, err := db.Prepare("UPDATE zol.amcs_product_features_values SET value=? WHERE product_id = ?;")
	checkErr(err)
	defer stmtProdUpdate.Close()

	for products.Next() {
		err := products.Scan(&product_code)
		checkErr(err)

		prodID := stmtProdID.QueryRow(product_code)
		checkErr(err)

		err = prodID.Scan(&product_id)
		checkErr(err)

		title := stmtProdTitle.QueryRow(product_id)
		err = title.Scan(&product_title)
		finded := re.FindString(product_title)
		if finded != "" {
        fmt.Printf("Found '%s'\n", finded)
			_, err = stmtProdUpdate.Exec(strings.TrimRight(product_title, finded), product_id)

			if err != nil {
				fmt.Println(err)
			} else {
				fmt.Print(".")
			}
		}
		//		skip++
		//		fmt.Println(product_code, "skip -", skip)
	}
	err = products.Err()
	checkErr(err)
}
