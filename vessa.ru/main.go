/* Catalog parser for http://www.vessa.ru */
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
	"golang.org/x/net/html/charset"
	"io/ioutil"
	//	"os"
	//	"path/filepath"
	"net/http"
	"strconv"
	"strings"
	"time"
) // }}}

// Product structure
type Product struct { // {{{
	Sku      string            `json:"sku"`
	Category string            `json:"category"`
	Name     string            `json:"name"`
	Desc     map[string]string `json:"desc"`
	Pict     string            `json:"pict"`
	Link     string            `json:"link"`
	//	Desc []string `json:"desc"`
} // }}}

// Products array
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
	excludeList       = []string{
		"Lora Grig",
		"Товары для дома",
	}
) // }}}

const ( // {{{
	baseURL    = "http://www.vessa.ru"
	outputDir  = "images"
	outputJSON = "catalog.json"
) // }}}

func checkErr(msg string, err error) { // {{{
	if err != nil {
		fmt.Printf("%s - %s\n", msg, err)
	}
} // }}}

func getFilename(url string) string { // {{{
	tokens := strings.Split(url, "/")
	return tokens[len(tokens)-1]
} // }}}

/* func translit(s string) string { // {{{
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

func getPageDoc(url string) *goquery.Document { // {{{
	client := &http.Client{}
	req, err := http.NewRequest("GET", url, nil)
	checkErr("ERROR in getPageDoc::NewRequest", err)
	req.Header.Add("Accept-Encoding", "identity")
	req.Close = true

	response, err := client.Do(req)
	checkErr("ERROR in getPageDoc::Do", err)

	defer response.Body.Close()

	utf8, err := charset.NewReader(response.Body, response.Header.Get("Content-Type"))
	checkErr("Encoding error", err)

	//	body, err := ioutil.ReadAll(utf8)

	//	doc, err := goquery.NewDocumentFromResponse(response)
	doc, err := goquery.NewDocumentFromReader(utf8)
	checkErr("ERROR in getPageDoc::NewDocumentFromResponse", err)
	return doc
} // }}}

func getCategories() map[string]string { // {{{
	fmt.Println("* Поиск категорий")
	//	reg, _ := regexp.Compile(`\s\([0-9]+\)`)

	getPageDoc(baseURL + "/catalog/").Find("body > div.page > div.wrapper > div.text-page.catalog-index > div.body > div.brands > div.item").Each(func(i int, s *goquery.Selection) {
		catLink, _ := s.Find("div.logo > a").Attr("href")
		cat, _ := s.Find("div.logo > a > img").Attr("alt")
		//		fmt.Printf("** %s => %s\n", cat, catLink)
		catLink = strings.TrimSpace(catLink)
		if !contains(excludeList, cat) {
			//			fmt.Printf("%s => %s\n", cat, strings.TrimSpace(catLink))
			subDoc := getPageDoc(baseURL + catLink)
			//			fmt.Printf("Curr subCat link => '%s'\n", baseURL+"/"+catLink)
			subDoc.Find("body > div > div > div.card > div.body > div > table > tbody > tr > td.categories > ul > li").Each(func(j int, ss *goquery.Selection) {
				if ss.Find("a").HasClass("top-level") {
					subCat := strings.TrimSpace(ss.Find("a.top-level").Text())
					subCatLink, _ := ss.Find("a.top-level").Attr("href")
					fmt.Printf("** %s - %s => %s\n", cat, subCat, baseURL+subCatLink)
					categories[cat+" - "+subCat] = baseURL + subCatLink
				}
			})
		}
	})
	fmt.Println("* Поиск категорий закончен")
	return categories
} // }}}

func getPages(url string) int { // {{{
	doc, _ := goquery.NewDocument(url)
	last, _ = strconv.Atoi(doc.Find("div.pagination > div.pages > a").Last().Text())
	if last = last; last == 0 {
		last = 1
	}
	//	fmt.Printf("Найдено страниц для %s - %d\n", url, last)
	return last
} // }}}

func walkinPages() { // {{{
	fmt.Printf("* Начинаем обработку страниц\n")
	for cat, catLink := range categories {
		last := getPages(catLink)
		for i := 0; i < last; i++ {
			currPage := fmt.Sprintf("%s%s%s%s", catLink, "?skip=", strconv.Itoa(i), "0")
			//		fmt.Printf("** Обработка страницы %s - %s\n", cat, currPage)
			//		doc, _ = goquery.NewDocument(currPage)
			getProducts(getPageDoc(currPage), cat)
		}
	}
	wg.Wait()
	fmt.Printf("\n* Закончили обработку страниц\n")
} // }}}

func getProducts(doc *goquery.Document, cat string) { // {{{
	//	ret, _ := doc.Html()
	//	fmt.Printf("getProducts doc - %s\n", ret)
	doc.Find("#products > div.item").Each(func(i int, s *goquery.Selection) {
		link, _ := s.Find("table > tbody > tr > td:nth-child(2) > h2 > a").First().Attr("href")
		// fmt.Printf("*** Получена ссылка для %s - %s\n", cat, strings.Replace(link, "	", "", -1))
		//		fmt.Printf("*** Получена ссылка для %s - %s\n", cat, link)
		wg.Add(1)
		go getProductInfo(baseURL+link, cat)
	})
} // }}}

func getProductInfo(url string, cat string) { // {{{
	defer wg.Done()
	//	fmt.Printf("**** Обрабатываем продукт %s - %s\n", cat, url)
	docp := getPageDoc(url)

	p := Product{}
	tmpSKU := docp.Find("#products > div > table > tbody > tr > td:nth-child(2) > h2 > a").Text()
	if tmpSKU == "" {
		failed = append(failed, url)
		return
	}
	//	p.Sku = strings.TrimLeft(strings.TrimSpace(tmpSKU), "Артикул: ")
	p.Sku = tmpSKU
	p.Name = tmpSKU
	tmpPict, _ := docp.Find("#products > div > table > tbody > tr > td:nth-child(1) > div > a").Attr("href")
	p.Pict = baseURL + "/" + tmpPict
	p.Link = url
	p.Category = cat

	desc := make(map[string]string)
	docp.Find("#products > div > table > tbody > tr > td:nth-child(2) > div.properties > div.row").Each(func(i int, sa *goquery.Selection) {
		title := strings.TrimRight(sa.Find("table > tbody > tr > td.label > label").Text(), ":")
		if title != "" {
			desc[title] = strings.TrimSpace(strings.TrimLeft(strings.TrimSpace(sa.Find(" table > tbody > tr > td:nth-child(2)").Text()), title+":"))
			//		fmt.Printf("%s => %s\n", title, desc[title])
		} else if sa.HasClass("product_description") {
		}
	})
	desc["Описание"] = strings.TrimSpace(docp.Find("#products > div > table > tbody > tr > td:nth-child(2) > div.note").Text())
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
	fmt.Println("* Сбор страниц закончен")

	/* var cats []string
	for cat := range categories {
		cats = append(cats, cat)
	}
	sort.Strings(cats)

	for _, cat := range cats {
		fmt.Printf("%s => %s\n", cat, categories[cat])
	} */

	fmt.Printf("* Обработано продуктов %d\n", counter)
	if len(failed) > 0 {
		fmt.Printf("Не обработано - %d\n", len(failed))
		for _, fail := range failed {
			fmt.Printf("FAIL => %s\n", fail)
		}
	}
	te := time.Now()
	d := te.Sub(ts)
	fmt.Printf("Общее время выполнения в секундах - %d\n", int(d.Seconds()))
} // }}}
