/* Catalog parser for http://orby.ru */
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
	"net/url"
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
		"Lookbook",
	}
) // }}}

const ( // {{{
	baseURL    = "http://orby.ru"
	outputDir  = "images"
	outputJSON = "orby.json"
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

func cleanPicURL(picUrl string) string { // {{{
	u, err := url.Parse(picUrl)
	checkErr("ERROR in cleanPicURL", err)
	newPicURL := fmt.Sprintf("%s://%s%s", u.Scheme, u.Host, u.Path)
	return newPicURL
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

// Set sleep time out for request
func sleep(n time.Duration) {
	time.Sleep(n * time.Second)
}

func getPageDoc(url string) *goquery.Document { // {{{
	client := &http.Client{}
	req, err := http.NewRequest("GET", url, nil)
	checkErr("ERROR in getPageDoc::NewRequest", err)
	req.Header.Add("Accept-Encoding", "identity")
	req.Header.Set("User-Agent", "bvp Spider Bot v.0.1")
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

	getPageDoc(baseURL).Find("#top_white > div.menus > ul.menu_bottom > li").Each(func(i int, s *goquery.Selection) {
		cat := strings.TrimSpace(s.Find("a").First().Text())
		//		catLink, _ := s.Find("a").First().Attr("href")
		if !contains(excludeList, cat) {
			//			fmt.Printf("** %s => %s\n", cat, catLink)
			//			fmt.Printf("%s => %s\n", cat, strings.TrimSpace(catLink))
			//			fmt.Printf("Curr subCat link => '%s'\n", baseURL+"/"+catLink)
			s.Find("div.drop_down > div.cont > div.positions > div.col > a").Each(func(j int, ss *goquery.Selection) {
				subCat := strings.TrimSpace(ss.Text())
				subCatLink, _ := ss.Attr("href")
				fmt.Printf("** %s - %s => %s\n", cat, subCat, baseURL+subCatLink)
				categories[cat+" - "+subCat] = baseURL + subCatLink
			})
		}
	})
	sleep(2)
	fmt.Println("* Поиск категорий закончен")
	return categories
} // }}}

func getPages(url string) int { // {{{
	sleep(2)
	doc, _ := goquery.NewDocument(url)
	last, _ = strconv.Atoi(doc.Find("#catalog-list > ul.paging > li > a:not(.next)").Last().Text())
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
			//		currPage := fmt.Sprintf("%s%s%s%s", catLink, "?skip=", strconv.Itoa(i), "0")
			//		fmt.Printf("** Обработка страницы %s - %s\n", cat, currPage)
			//		doc, _ = goquery.NewDocument(currPage)
			sleep(2)
			getProducts(getPageDoc(catLink), cat)
		}
	}
	//	wg.Wait()
	fmt.Printf("\n* Закончили обработку страниц\n")
} // }}}

func getProducts(doc *goquery.Document, cat string) { // {{{
	//	ret, _ := doc.Html()
	//	fmt.Printf("getProducts doc - %s\n", ret)
	doc.Find(`div#products > div.product-item`).Each(func(i int, s *goquery.Selection) {
		link, _ := s.Find("a.title").First().Attr("href")
		pic, _ := s.Find("div.photo > div > a > img").Attr("src")
		// fmt.Printf("*** Получена ссылка для %s - %s\n", cat, strings.Replace(link, "	", "", -1))
		//		fmt.Printf("*** Получена ссылка для %s - %s\n", cat, link)
		//		wg.Add(1)
		sleep(2)
		getProductInfo(baseURL+link, baseURL+pic, cat)
	})
} // }}}

func getProductInfo(url string, pic string, cat string) { // {{{
	//	defer wg.Done()
	//	fmt.Printf("**** Обрабатываем продукт %s - %s\n", cat, url)
	docp := getPageDoc(url)

	p := Product{}
	tmpSKU := docp.Find("div#product-description > span.prod_number").Text()
	if tmpSKU == "" {
		failed = append(failed, url)
		//		html, _ := docp.Html()
		//		fmt.Printf("%s - %s\n", url, html)
		return
	}
	//	p.Sku = strings.TrimLeft(strings.TrimSpace(tmpSKU), "Артикул: ")
	p.Sku = tmpSKU
	p.Name = docp.Find("div#product-description > span.title").Text()
	p.Pict = cleanPicURL(pic)
	p.Link = url
	p.Category = cat

	desc := make(map[string]string)

	/* descHtml, _ := docp.Find("#content-katalog > div > div > div.karta-wrapper > table > tbody > tr:nth-child(2) > td:nth-child(1) > p.kat_param_p").Html()
	descSlice := strings.Split(descHtml, "<br/>")
	for _, v := range descSlice {
		d := strings.Split(v, ":")
		if !contains(excludeList, d[0]) {
			if d[0] == "Артикул" {
				p.Sku = strings.TrimSpace(d[1])
			} else {
				desc[d[0]] = strings.TrimSpace(d[1])
			}
		}
	} */
	desc["Состав"] = strings.TrimSpace(docp.Find("#product-description > div.ForParam").Text())
	desc["Описание"] = strings.TrimSpace(docp.Find("#product-description > span.desc.ForText").Text())
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
