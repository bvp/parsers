/*
Catalog parser for proecolife.ru
*/
package main

import (
	"encoding/json"
	"errors"
	"fmt"
	"github.com/PuerkitoBio/goquery"
	"io"
	"io/ioutil"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	//	"time"
)

var (
	first, last, curr int
	doc               *goquery.Document
	currDoc           *goquery.Document
	currProduct       *goquery.Document
	products          Products
)

const (
	baseURL    = "http://www.button-blue.com/catalog/"
	outputDir  = "images"
	outputCSV  = "button-blue.csv"
	outputJSON = "button-blue.json"
)

/*
Product structure
*/
type Product struct {
	Art      string `json:"Article"`
	Title    string `json:"Title"`
	Category string `json:"Category"`
	Sex      string `json:"Sex"`
	Material string `json:"Material"`
	Cloth    string `json:"Cloth"`
	Print    string `json:"Print"`
	//	Age      []string `json:"Age"`
	Age string `json:"Age"`
	//	Size     []string `json:"Size"`
	Size  string `json:"Size"`
	Color string `json:"Color"`
	Img   string `json:"Image"`
	//	Link     string   `json:"URL"`
}

type Catalog struct {
	Sku  string            `json:"sku"`
	Name string            `json:"name"`
	Desc map[string]string `json:"desc"`
	Pict string            `json:"pict"`
	Link string            `json:"link"`
}

/*
Products array
*/
type Products []Product

func checkErr(err error) {
	if err != nil {
		log.Fatal(err)
	}
}

func getFilename(url string) string {
	tokens := strings.Split(url, "/")
	return tokens[len(tokens)-1]
}

func downloadFromURL(url string, outputDir string) error {
	fileName := getFilename(url)
	//	fmt.Println("Загрузка", url, "в", outputDir+string(filepath.Separator)+fileName)

	// TODO: check file existence first with io.IsExist
	output, err := os.Create(outputDir + string(filepath.Separator) + fileName)
	if err != nil {
		log.Println("Ошибка при создании", outputDir+string(filepath.Separator)+fileName, "-", err)
		return err
	}
	defer output.Close()

	response, err := http.Get(url)
	if err != nil {
		log.Println("Ошибка при загрузке", url, "-", err)
		return err
	}
	defer response.Body.Close()

	// n, err := io.Copy(output, response.Body)
	n, err := io.Copy(output, response.Body)
	if err != nil {
		log.Println("Ошибка при загрузке", url, "-", err)
		return err
	}

	//	fmt.Println(n, "байтов загружено.")
	if n == 0 {
		return errors.New("Null size image")
	} else {
		return nil
	}
}

func getPages(doc *goquery.Document) {
	first, _ = strconv.Atoi(doc.Find(".catalog-objects-pagination > li").First().Text())
	last, _ = strconv.Atoi(doc.Find(".catalog-objects-pagination > li").Last().Text())
	log.Printf("Найдено %d страниц\n", last)
}

func walkinPages() {
	for i := first; i <= last; i++ {
		currPage := fmt.Sprintf("%s%s%d", baseURL, "?pageNum=", i)
		log.Printf("Текущая страница %s\n", currPage)
		doc, _ = goquery.NewDocument(currPage)
		getProductsList(doc)
	}
}

func getProductsList(doc *goquery.Document) {
	doc.Find(".catalog-objects-group").Each(func(i int, s *goquery.Selection) {
		s.Find("div.global-col").Each(func(i int, item *goquery.Selection) {
			link, _ := item.Find("a.catalog-objects-group-pic-link").Attr("href")
			//time.Sleep(1 * time.Second)
			getProductInfo(link)
		})
	})
}

func getProductInfo(url string) {
	p := Product{}
	currProduct, _ = goquery.NewDocument(baseURL + url)
	log.Printf("Парсим продукт %s%s", baseURL, url)
	p.Title = currProduct.Find("div.catalog-one-info-hd > div > h2").Text()
	img, _ := currProduct.Find("div.catalog-one-gal-pic > a > img").Attr("src")
	p.Img = getFilename(img)
	if downloadFromURL("http://www.proecolife.ru"+img, outputDir) != nil {
		//
	}
	currProduct.Find("div.catalog-one-info-desc-feature > div.catalog-one-info-desc-feature-row").Each(func(i int, s *goquery.Selection) {
		trimmed := strings.TrimSpace(s.Text())
		splitted := strings.Split(trimmed, "...................")
		switch splitted[0] {
		case "Артикул":
			p.Art = strings.TrimSpace(splitted[1])
		case "Категория":
			p.Category = splitted[1]
		case "Пол":
			p.Sex = splitted[1]
		case "Состав (материал)":
			p.Material = splitted[1]
		case "Полотно":
			p.Cloth = splitted[1]
		case "Вид печати":
			p.Print = splitted[1]
		case "Возраст":
			// p.Age = strings.Split(splitted[1], " / ")
			p.Age = splitted[1]
		}
	})

	var arrSize []string
	currProduct.Find("div.catalog-one-info-desc > div.catalog-one-info-size > div.fix > span").Each(func(i int, s *goquery.Selection) {
		// p.Size = append(p.Size, s.Text())
		arrSize = append(arrSize, s.Text())
	})
	p.Size = strings.Join(arrSize, " / ")
	currProduct.Find("div.catalog-one-info-desc > div.catalog-one-info-color > div.fix > span").Each(func(i int, s *goquery.Selection) {
		p.Color = s.Text()
	})

	//	csvdatafile, err := os.OpenFile(outputCSV, os.O_APPEND|os.O_RDWR, 0600)
	//	checkErr(err)
	//	defer csvdatafile.Close()

	//	writer := csv.NewWriter(csvdatafile)
	//	var record []string
	//	record = append(record, p.Art)
	//	record = append(record, p.Category)
	//	record = append(record, p.Title)
	//	record = append(record, p.Sex)
	//	record = append(record, p.Material)
	//	record = append(record, p.Cloth)
	//	record = append(record, p.Size)
	//	record = append(record, p.Age)
	//	record = append(record, p.Img)
	//	writer.Write(record)
	//	writer.Flush()
	//	jsonProduct, _ := json.MarshalIndent(p, "", "  ")
	//	fmt.Println(string(jsonProduct))
	products = append(products, p)
}

func main() {
	if _, err := os.Stat(outputDir); os.IsNotExist(err) {
		os.MkdirAll(outputDir, 0777)
	}
	if _, err := os.Stat(outputCSV); os.IsExist(err) {
		os.Remove(outputCSV)
	}
	csvfile, _ := os.Create(outputCSV)
	csvfile.WriteString("article,category,title,sex,material,cloth,size,age,img\n")
	defer csvfile.Close()

	log.Println("Начинаем сбор страниц")
	doc, err := goquery.NewDocument(baseURL)
	checkErr(err)
	getPages(doc)
	walkinPages()

	jsonProducts, _ := json.MarshalIndent(products, "", "  ")
	err = ioutil.WriteFile(outputJSON, jsonProducts, 0644)
	if err != nil {
		panic(err)
	}
	log.Println("Сбор страниц закончен")
}
