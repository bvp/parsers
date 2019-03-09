/* Catalog parser for https://visavis-fashion.ru */
package main

import ( // {{{
	"encoding/json"
	//	"sort"
	"sync"
	//	"regexp"
	"fmt"

	"github.com/PuerkitoBio/goquery"
	//	"github.com/fiam/gounidecode/unidecode"
	//	"io"
	"io/ioutil"
	//	"os"
	//	"path/filepath"
	"strconv"
	"strings"
	"time"
) // }}}

/*
Product structure
*/
type Product struct { // {{{
	Sku      string            `json:"sku"`
	Category string            `json:"category"`
	Name     string            `json:"name"`
	Desc     map[string]string `json:"desc"`
	Pict     string            `json:"pict"`
	Link     string            `json:"link"`
	//	Desc []string `json:"desc"`
} // }}}

/*
Products array
*/
type Products []Product

var ( // {{{
	first, last, curr int
	doc               *goquery.Document
	currDoc           *goquery.Document
	currProduct       *goquery.Document
	products          Products
	categories        = make(map[string]string)
	err               error
	counter           int
	failed            []string
	wg                sync.WaitGroup
) // }}}

const ( // {{{
	baseURL    = "http://visavis-fashion.ru"
	outputDir  = "images"
	outputJSON = "visavis-fashion.json"
) // }}}

func checkErr(err error) { // {{{
	if err != nil {
		fmt.Println(err)
	}
} // }}}

func getFilename(url string) string { // {{{
	tokens := strings.Split(url, "/")
	return tokens[len(tokens)-1]
} // }}}

/* func translit(s string) string {// {{{
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
} */ // }}}

func contains(slice []string, item string) bool { // {{{
	set := make(map[string]struct{}, len(slice))
	for _, s := range slice {
		set[s] = struct{}{}
	}

	_, ok := set[item]
	return ok
} // }}}

func getCategories() map[string]string { // {{{
	excludeList := []string{
		"новинки",
		"Акции",
	}
	fmt.Println("* Поиск категорий")
	//	reg, _ := regexp.Compile(`\s\([0-9]+\)`)

	doc, _ := goquery.NewDocument(baseURL)
	doc.Find("body > div.main_outer > div > div.header_row_3.clearfix > div > ul > li.level_0").Each(func(i int, s *goquery.Selection) {
		//		catLink, _ := s.Find("a.level_0").Attr("href")
		cat := s.Find("a.level_0").Text()
		if !contains(excludeList, cat) {
			s.Find("div.subnav_outer > div.subnav_inner > div.fl > dl > dt > a").Each(func(j int, sc *goquery.Selection) {
				subCat := sc.Text()
				subCatLink, _ := sc.Attr("href")
				fmt.Printf("** %s - %s => %s\n", cat, subCat, baseURL+subCatLink)
				categories[cat+" - "+subCat] = baseURL + subCatLink
			})
		}
	})
	fmt.Println("* Поиск категорий закончен")
	return categories
} // }}}

func getPages(url string) int { // {{{
	doc, _ := goquery.NewDocument(url)
	last, _ = strconv.Atoi(doc.Find("div.g-title-wrap > div.pages > a:not(.next)").Last().Text())
	if last = last; last == 0 {
		last = 1
	}
	//	fmt.Printf("Найдено страниц - %d\n", last)
	return last
} // }}}

func walkinPages() { // {{{
	fmt.Printf("* Начинаем обработку страниц\n")
	for cat, catLink := range categories {
		last := getPages(catLink)
		for i := 1; i <= last; i++ {
			currPage := fmt.Sprintf("%s%s%s", catLink, "?PAGEN_1=", strconv.Itoa(i))
			//			fmt.Printf("** Обработка страницы %s - %s\n", cat, currPage)
			doc, _ = goquery.NewDocument(currPage)
			getProducts(doc, cat)
		}
	}
	wg.Wait()
	fmt.Printf("\n** Закончили обработку страниц\n")
} // }}}

func getProducts(doc *goquery.Document, cat string) { // {{{
	doc.Find(" div.content_right > div.catalog_block > div.catalog_block-item").Each(func(i int, s *goquery.Selection) {
		link, _ := s.Find("div.info > div.name > a").Attr("href")
		//		fmt.Printf("*** Получена ссылка для %s - %s\n", cat, link)
		go getProductInfo(baseURL+link, cat)
		wg.Add(1)
	})
} // }}}

func getProductInfo(url string, cat string) { // {{{
	defer wg.Done()
	//	fmt.Printf("**** Обрабатываем продукт %s - %s\n", cat, url)
	docp, err := goquery.NewDocument(url)
	checkErr(err)

	p := Product{}
	tmpSKU := docp.Find("div.catalog_item_detailed > div.col_right > div.count-comment-info.clearfix > div.count").Text()
	if tmpSKU == "" {
		failed = append(failed, url)
		return
	}
	p.Name = docp.Find("div.g-title-wrap > h1.g-title").Text()
	p.Sku = strings.TrimSpace(strings.Replace(tmpSKU, p.Name+" арт.", "", 1))
	tmpPict, _ := docp.Find("div.catalog_item_detailed.clearfix > div.col_left > div.img_big > a > img#zoomImg").Attr("src")
	p.Pict = baseURL + tmpPict
	p.Link = url
	p.Category = cat

	desc := make(map[string]string)
	docp.Find("div.catalog_item_detailed.clearfix > div.col_right > table.table-detail-info > tbody > tr").Each(func(i int, s *goquery.Selection) {
		title := s.Find("td:nth-child(1)").Text()
		if title != "Описание:" {
			tmpTitle := strings.TrimRight(title, ":")
			desc[tmpTitle] = s.Find("td:nth-child(2)").Text()
		} else {
			desc["Описание"] = strings.Replace(s.Find("td:nth-child(2)").Text(), "\n", "<br>", -1)
		}
	})
	p.Desc = desc

	//	fmt.Printf("%+v\n", p)

	products = append(products, p)
	counter++
	fmt.Print(".")
} // }}}

func main() { // {{{
	ts := time.Now()
	/*
		if _, err := os.Stat(outputDir); os.IsNotExist(err) {
			os.MkdirAll(outputDir, 0777)
		}
	*/
	fmt.Println("* Начинаем сбор страниц")
	categories = getCategories()

	walkinPages()
	//	doc, _ := goquery.NewDocument(baseURL + "?page=all")
	//	getProducts(doc)

	jsonProducts, _ := json.MarshalIndent(products, "", "  ")
	err = ioutil.WriteFile(outputJSON, jsonProducts, 0644)
	if err != nil {
		panic(err)
	}
	fmt.Println("\n* Сбор страниц закончен")

	/* var cats []string
	for cat := range categories {
		cats = append(cats, cat)
	}
	sort.Strings(cats)

	for _, cat := range cats {
		fmt.Printf("%s => %s\n", cat, categories[cat])
	} */

	fmt.Printf("* Обработано продуктов %d\n", counter)
	te := time.Now()
	d := te.Sub(ts)
	fmt.Printf("Общее время выполнения в секундах - %d\n", int(d.Seconds()))
	fmt.Printf("Не обработано - %d\n", len(failed))
	for _, fail := range failed {
		fmt.Printf("FAIL => %s\n", fail)
	}
} // }}}
