package main

import (
	"database/sql"
	"fmt"

	_ "github.com/go-sql-driver/mysql"
)

var (
	product_code string
	id           int
	skip         int
)

func checkErr(err error) {
	if err != nil {
		fmt.Println(err)
	}

}

func main() {
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

	stmtDups, err := db.Prepare("SELECT product_id FROM zol.amcs_products WHERE product_code = ? LIMIT 50 OFFSET 1;")
	checkErr(err)
	defer stmtDups.Close()

	stmtDupDel, err := db.Prepare("DELETE FROM zol.amcs_products WHERE product_id = ?")
	checkErr(err)
	defer stmtDupDel.Close()

	for products.Next() {
		err := products.Scan(&product_code)
		checkErr(err)

		dups, err := stmtDups.Query(product_code)
		checkErr(err)

		for dups.Next() {
			err := dups.Scan(&id)
			checkErr(err)

			_, err = stmtDupDel.Exec(id)

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
