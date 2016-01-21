/*
Catalog parser for anta-sport.ru
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

/*
Product structure
*/
type Product struct {
	Sku      string            `json:"sku"`
	Category string            `json:"category"`
	Name     string            `json:"name"`
	Desc     map[string]string `json:"desc"`
	Pict     string            `json:"pict"`
	Link     string            `json:"link"`
}

/*
Products array
*/
type Products []Product

type Catalog struct {
	Category string
	Products
}

var (
	first, last, curr int
	doc               *goquery.Document
	currDoc           *goquery.Document
	currProduct       *goquery.Document
	products          Products
	catalog           []Catalog
	categories        = make(map[string]string)
	err               error
)

const (
	baseURL    = "http://www.anta-sport.ru"
	outputDir  = "images"
	outputJSON = "anta.json"
)

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

func getCategories() map[string]string {
	log.Println("* Поиск категорий")
	doc, _ := goquery.NewDocument(baseURL + "/anta/kategorii/running")
	doc.Find("div.view-display-id-block > div.view-content > div.brand-anta-catecory").Each(func(i int, s *goquery.Selection) {
		cat := s.Find("div.views-field-name > span.field-content > a > div:nth-child(2)").Text()
		catLink, _ := s.Find("div.views-field-name > span.field-content > a").Attr("href")
		categories[cat] = catLink
	})
	return categories
}

func getPages(doc *goquery.Document) {
	first, _ = strconv.Atoi(doc.Find(".catalog-objects-pagination > li").First().Text())
	last, _ = strconv.Atoi(doc.Find(".catalog-objects-pagination > li").Last().Text())
	log.Printf("Найдено %d страниц\n", last)
}

func walkinPages() {
	for cat, catLink := range categories {
		log.Printf("%s => %s\n", cat, catLink)
		currPage := fmt.Sprintf("%s%s", baseURL, catLink)
		log.Printf("Текущая страница %s\n", currPage)
		doc, _ = goquery.NewDocument(currPage)
		getProductsList(doc, cat)
	}
}

func getProductsList(doc *goquery.Document, cat string) {
	doc.Find("div.view-content > div.productItem").Each(func(i int, s *goquery.Selection) {
		p := Product{}
		log.Println("** Парсим продукт")
		p.Sku = strings.TrimLeft(s.Find("div.catalog-item > div.full-text > p > span").Text(), "Арт. ")
		p.Name = strings.TrimSpace(s.Find("div.catalog-item > div.catitem-title").Text())
		p.Category = cat
		desc := make(map[string]string)
		desc["Описание"] = strings.TrimSpace(s.Find("div.catalog-item > div.full-text > p:nth-child(3)").Text())
		p.Desc = desc
		tmpPict, _ := s.Find("div.catalog-item > table.catitem-img > tbody > tr > td > a > img").Attr("src")
		p.Pict = strings.Split(tmpPict, "?")[0]
		//		p.Pict = tmpPict
		//		log.Printf("Pict - %s\n", p.Pict)
		//time.Sleep(1 * time.Second)
		if downloadFromURL(p.Pict, outputDir) != nil {
			log.Println("ERROR: Can't download image")
		}
		p.Link = baseURL + categories[cat]
		products = append(products, p)
	})
	ct := Catalog{cat, products}
	catalog = append(catalog, ct)
}

func getProductInfo(url string) {
	p := Product{}
	currProduct, _ = goquery.NewDocument(baseURL + url)
	//	p.Title = currProduct.Find("div.catalog-one-info-hd > div > h2").Text()
	//	img, _ := currProduct.Find("div.catalog-one-gal-pic > a > img").Attr("src")
	//	p.Img = getFilename(img)
	currProduct.Find("div.catalog-one-info-desc-feature > div.catalog-one-info-desc-feature-row").Each(func(i int, s *goquery.Selection) {
		trimmed := strings.TrimSpace(s.Text())
		splitted := strings.Split(trimmed, "...................")
		switch splitted[0] {
		case "Артикул":
			//			p.Art = strings.TrimSpace(splitted[1])
		case "Категория":
			//			p.Category = splitted[1]
		case "Пол":
			//			p.Sex = splitted[1]
		case "Состав (материал)":
			//			p.Material = splitted[1]
		case "Полотно":
			//			p.Cloth = splitted[1]
		case "Вид печати":
			//			p.Print = splitted[1]
		case "Возраст":
			// p.Age = strings.Split(splitted[1], " / ")
			//			p.Age = splitted[1]
		}
	})

	var arrSize []string
	currProduct.Find("div.catalog-one-info-desc > div.catalog-one-info-size > div.fix > span").Each(func(i int, s *goquery.Selection) {
		// p.Size = append(p.Size, s.Text())
		arrSize = append(arrSize, s.Text())
	})
	//	p.Size = strings.Join(arrSize, " / ")
	currProduct.Find("div.catalog-one-info-desc > div.catalog-one-info-color > div.fix > span").Each(func(i int, s *goquery.Selection) {
		//		p.Color = s.Text()
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
	log.Println("Начинаем сбор страниц")
	//	doc, err := goquery.NewDocument(baseURL)
	//	checkErr(err)
	categories = getCategories()

	//	getPages(doc)
	walkinPages()

	jsonProducts, _ := json.MarshalIndent(products, "", "  ")
	//	jsonProducts, _ := json.MarshalIndent(catalog, "", "  ")
	err = ioutil.WriteFile(outputJSON, jsonProducts, 0644)
	if err != nil {
		panic(err)
	}
	log.Println("Сбор страниц закончен")
}
