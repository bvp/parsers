/*
Catalog parser for bokatoys.ru
*/
package main

import (
	"encoding/json"
	"errors"
	"fmt"
	"github.com/PuerkitoBio/goquery"
	"github.com/fiam/gounidecode/unidecode"
	"io"
	"io/ioutil"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	"time"
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
	counter           int
)

const (
	baseURL    = "http://www.bokatoys.ru/"
	outputDir  = "images"
	outputJSON = "bokatoys.json"
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

func translit(s string) string {
	return unidecode.Unidecode(strings.Replace(s, " ", "_", -1))
}

func downloadFromURL(url string, outputDir string) error {
	fileName := getFilename(url)
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
	doc, _ := goquery.NewDocument(baseURL + "/catalog/")
	doc.Find("#catalog_menu > ul > li > a").Each(func(i int, s *goquery.Selection) {
		//		cat := s.Find("a").Text()
		//		catLink, _ := s.Find("a").Attr("href")
		cat := s.Text()
		tmpcatLink, _ := s.Attr("href")
		catLink := strings.TrimRight(tmpcatLink, "#con")
		categories[cat] = catLink
		log.Printf("** Найдена категория - %s\n", cat)
	})
	log.Println("* Поиск категорий закончен")
	return categories
}

func getPages(doc *goquery.Document) {
	first, _ = strconv.Atoi(doc.Find(".catalog-objects-pagination > li").First().Text())
	last, _ = strconv.Atoi(doc.Find(".catalog-objects-pagination > li").Last().Text())
	log.Printf("\tНайдено %d страниц\n", last)
}

func walkinPages() {
	for cat, catLink := range categories {
		// log.Printf("%s => %s\n", cat, catLink)
		currPage := fmt.Sprintf("%s%s%s", baseURL, catLink, "&page=all")
		log.Printf("** Открываем страницу %s\n", currPage)
		doc, _ = goquery.NewDocument(currPage)
		getProducts(doc, cat)
		log.Printf("** Закрываем страницу %s\n", currPage)
	}
}

func getProducts(doc *goquery.Document, cat string) {
	doc.Find("#content > ul.products > li.product").Each(func(i int, s *goquery.Selection) {
		p := Product{}
		log.Println("*** Парсим продукт")
		p.Sku = strings.TrimSpace(s.Find("form.variants > table > tbody > tr.variant > td:nth-child(2) > p.var_art > span").Text())

		p.Name = strings.TrimSpace(s.Find("div.product_info > h3 > a").Text())

		p.Category = cat

		tmpLink, _ := s.Find("div.product_info > h3 > a").Attr("href")
		p.Link = baseURL + tmpLink

		desc := make(map[string]string)
		docProd, _ := goquery.NewDocument(p.Link)
		docProd.Find("#content > div.product").Each(func(i int, dp *goquery.Selection) {
			if dp.Find("div.description > div:nth-child(1) > span > span.first").Text() == "Описание:" {
				tmpDesc := strings.TrimSpace(dp.Find("div.description > p").Text())
				if tmpDesc != "" {
					desc["Описание"] = tmpDesc
				}
			}
			if p.Sku == "" {
				tmpSkuLabel := strings.Trim(dp.Find("div.description > div[itemprop=offers] > form.variants > table > tbody > tr.variant > td:nth-child(2) > p.var_art").Text(), dp.Find("div.description > div[itemprop=offers] > form.variants > table > tbody > tr.variant > td:nth-child(2) > p.var_art > span").Text())
				if tmpSkuLabel != "" {
					p.Sku = strings.TrimSpace(dp.Find("div.description > div[itemprop=offers] > form.variants > table > tbody > tr.variant > td:nth-child(2) > p.var_art > span").Text())
				}
			}
			//			tmpSkuLbl := dp.Find("div.description > div[itemprop=offers] > form.variants > table > tbody > tr.variant > td:nth-child(2) > p.var_art").Text()
			//			desc["Артикул"] = strings.TrimRight(tmpSkuLbl, tmpSkuVal)
			dp.Find("ul.features > li").Each(func(i int, dpf *goquery.Selection) {
				desc[strings.TrimSpace(dpf.Find("label").Text())] = strings.TrimSpace(dpf.Find("span").Text())
			})
		})
		p.Desc = desc

		tmpPict, _ := s.Find("div.image > a > img").Attr("src")
		p.Pict = strings.Split(tmpPict, "?")[0]
		//		p.Pict = tmpPict
		//		log.Printf("Pict - %s\n", p.Pict)
		time.Sleep(1 * time.Second)
		if downloadFromURL(p.Pict, outputDir) != nil {
			log.Println("ERROR: Can't download image")
		}
		products = append(products, p)
		counter++
	})
	ct := Catalog{cat, products}
	catalog = append(catalog, ct)
}

func main() {
	ts := time.Now()
	if _, err := os.Stat(outputDir); os.IsNotExist(err) {
		os.MkdirAll(outputDir, 0777)
	}
	log.Println("* Начинаем сбор страниц")
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
	log.Println("* Сбор страниц закончен")
	log.Printf("* Обработано продуктов %d\n", counter)
	te := time.Now()
	d := te.Sub(ts)
	log.Printf("Общее время выполнения в секундах - %f\n", d.Seconds())
}
